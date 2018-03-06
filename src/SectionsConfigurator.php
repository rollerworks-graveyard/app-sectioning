<?php

declare(strict_types=1);

/*
 * This file is part of the Rollerworks AppSectioningBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\AppSectioning;

use Rollerworks\Component\AppSectioning\Exception\ValidatorException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RequestMatcher;

/**
 * SectionsConfigurator resolves the app-sections configuration
 * and ensures there are no conflicts.
 *
 * Say there are two sections:
 *
 * * Frontend - example.com/
 * * Backend  - example.com/backend/
 *
 * Unless the 'backend' section is tried earlier the 'frontend' will always match!
 * To prevent this the path (regex) is configured to never match 'backend/'.
 * Only when both match the same host and only when there is a prefix conflict.
 *
 * Note: For routing the prefix doesn't use a negative look-ahead,
 * as the router performs full matches.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @internal
 */
final class SectionsConfigurator
{
    /**
     * @var SectionConfiguration[]
     */
    private $sections = [];
    private $exportedSections = [];
    private $processed;

    public function set(string $name, SectionConfiguration $config)
    {
        $this->sections[$name] = $config;

        if (null !== $this->processed) {
            throw new \RuntimeException('Cannot register new sections after processing.');
        }
    }

    public function process(): void
    {
        if (null !== $this->processed) {
            return;
        }

        $conflicts = [];
        $this->processed = $this->groupSectionsPerHost();

        foreach ($this->processed as $hostIndex => $sectionsInHost) {
            $prefixes = [];

            /** @var SectionConfiguration $config */
            foreach ($sectionsInHost as $section => $config) {
                $prefix = $config->prefix;

                if (isset($prefixes[$prefix])) {
                    $conflicts[$prefixes[$prefix]][] = $section;
                } else {
                    $prefixes[$prefix] = $section;
                }

                $this->sections[$section]->path = $this->generateSectionPath($section, $sectionsInHost);
                $this->exportedSections[$section] = $this->sections[$section]->toArray();
            }
        }

        if (\count($conflicts)) {
            throw ValidatorException::sectionsConfigConflict($this->formatPrefixConflicts($conflicts));
        }
    }

    /**
     * Register the sections configuration as service-container parameters
     * and a RequestMatcher service.
     *
     * The following pattern is used, where '{service-prefix}' is the value of $servicePrefix
     * and '{section-name}' the name the processed section (repeated for each registered section).
     *
     * Like:
     *
     * '{service-prefix}.{section-name}.is_secure'        => false
     * '{service-prefix}.{section-name}.channel'          => null
     * '{service-prefix}.{section-name}.host'             => 'example.com'
     * '{service-prefix}.{section-name}.host_pattern'     => '^example\.com$'
     * '{service-prefix}.{section-name}.prefix'           => '/'
     * '{service-prefix}.{section-name}.path'             => '^/(?!(backend|api)/)'
     * '{service-prefix}.{section-name}.request_matcher'  => {RequestMatcher service}
     *
     * Note: when the host is empty the 'host_pattern' is null.
     *
     * The `channel` is only set to 'https' when is_secure is true. This prevents forcing
     * an HTTP channel when HTTPS is used. HTTPS is only enforced when configured.
     *
     * @param ContainerBuilder $container
     * @param string           $servicePrefix
     */
    public function registerToContainer(ContainerBuilder $container, string $servicePrefix): void
    {
        $this->process();
        $servicePrefix = rtrim($servicePrefix, '.').'.';

        foreach ($this->sections as $name => $config) {
            $container->setParameter($servicePrefix.$name.'.is_secure', $config->isSecure);
            $container->setParameter($servicePrefix.$name.'.channel', $config->isSecure ? 'https' : null);
            $container->setParameter($servicePrefix.$name.'.domain', $config->domain);
            $container->setParameter($servicePrefix.$name.'.host', $config->host);
            $container->setParameter($servicePrefix.$name.'.host_pattern', $config->hostPattern);
            $container->setParameter($servicePrefix.$name.'.requirements', $config->requirements);
            $container->setParameter($servicePrefix.$name.'.defaults', $config->defaults);
            $container->setParameter($servicePrefix.$name.'.prefix', $config->prefix);
            $container->setParameter($servicePrefix.$name.'.path', $config->path);
            $container->register($servicePrefix.$name.'.request_matcher', RequestMatcher::class)->setArguments(
                [
                    $container->getParameterBag()->escapeValue($config->path),
                    $container->getParameterBag()->escapeValue($config->hostPattern),
                ]
            );
        }
    }

    /**
     * Returns resolved sections.
     *
     * The returned structure is as follow (value is an associative array):
     * [section-name] => [is_secure, host, host_pattern, prefix, path]
     *
     * @return array[]
     */
    public function exportConfiguration(): array
    {
        $this->process();

        return $this->exportedSections;
    }

    private function groupSectionsPerHost(): array
    {
        $hostsSections = [];
        $sections2 = $this->sections;
        $processed = [];
        $hostIndex = 0;

        foreach ($this->sections as $name => $config) {
            if (isset($processed[$name])) {
                continue;
            }

            $processed[$name] = $hostIndex;
            $hostsSections[$hostIndex][$name] = $config;

            foreach ($sections2 as $name2 => $config2) {
                if ($config->hostEquals($config2)) {
                    $hostsSections[$processed[$name]][$name2] = $config2;
                    $processed[$name2] = $processed[$name];
                }
            }

            ++$hostIndex;
        }

        return $hostsSections;
    }

    private function formatPrefixConflicts(array $conflicts): array
    {
        $failedSections = [];

        foreach ($conflicts as $primary => $sections) {
            $failedSections[$primary] = [
                $this->sections[$primary]->hostPattern,
                $this->sections[$primary]->prefix,
                $sections,
            ];
        }

        return $failedSections;
    }

    private function generateSectionPath(string $name, array $allSections): string
    {
        $config = $allSections[$name];
        unset($allSections[$name]);

        $negativePrefixes = [];
        foreach (array_column($allSections, 'prefix') as $prefix) {
            $negativePrefixes[] = $this->findNoneMatchingPath($config->prefix, $prefix);
        }

        $negativePath = implode('|', array_map('preg_quote', array_filter(array_unique($negativePrefixes))));
        $path = '^/'.preg_quote(ltrim($config->prefix, '/'), '#');

        if ($negativePath) {
            $path .= "(?!($negativePath)/)";
        }

        return $path;
    }

    private function findNoneMatchingPath(string $currentPrefix, string $otherPrefix): string
    {
        $prefix = array_filter(explode('/', $currentPrefix));
        $prefixes = array_filter(explode('/', $otherPrefix));
        $matches = false;

        foreach ($prefixes as $i => $secondaryPrefix) {
            if (!isset($prefix[$i])) {
                if (0 === $i || $matches) {
                    // It matched so far, so both share the same prefix,
                    // but the next part is unique to other-prefixes and should not be matched.
                    // Or the index is 0 (/) and will always match.

                    return $secondaryPrefix;
                }

                break;
            }

            if ($secondaryPrefix === $prefix[$i]) {
                $matches = true;
            } else {
                if ($matches) {
                    // It matched so far, so both share the same prefix,
                    // but the next part is unique to other-prefixes and should not be matched.
                    return $secondaryPrefix;
                }

                break;
            }
        }

        return '';
    }
}
