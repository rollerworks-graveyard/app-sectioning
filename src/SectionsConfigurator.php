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
 * * Frontend - host: example.com prefix: /
 * * Backend  - host: example.com prefix: backend/
 *
 * Unless the 'backend' section is tried earlier the 'frontend' will always match!
 * To prevent this the path (regex) is configured to never match 'backend/'.
 * Only when both share the same host and only there is an actual conflict.
 *
 * Note: For routing the prefix doesn't use a negative look-ahead,
 * as router performs full matches.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class SectionsConfigurator
{
    private $sections = [];
    private $processed;

    public function set(string $name, SectionConfiguration $config)
    {
        $this->sections[$name] = $config->getConfig();
        $this->sections[$name]['config'] = $config;
        $this->processed = null;
    }

    /**
     * Process the registered sections configuration.
     *
     * @throws ValidatorException When one ore more sections have a conflicting configuration
     */
    public function process(): void
    {
        $conflicts = [];
        $this->processed = $this->groupSectionsPerHost();

        foreach ($this->processed as $hostIndex => $configs) {
            $prefixes = [];

            foreach ($configs as $section => $config) {
                $prefix = $config['prefix'];

                if (isset($prefixes[$prefix])) {
                    $conflicts[$prefixes[$prefix]][] = $section;
                } else {
                    $prefixes[$prefix] = $section;
                }
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
     * '{service-prefix}.{section-name}.host'             => 'example.com'
     * '{service-prefix}.{section-name}.host_pattern'     => '^example\.com$'
     * '{service-prefix}.{section-name}.prefix'           => '/'
     * '{service-prefix}.{section-name}.path'             => '^/(?!(backend|api)/)'
     * '{service-prefix}.{section-name}.request_matcher'  => {RequestMatcher service}
     *
     * Note: when the host is empty the 'host_pattern' is '.*' (as the route requirement)
     * cannot be empty. The host pattern for the request_matcher is null then.
     *
     * @param ContainerBuilder $container
     */
    public function registerToContainer(ContainerBuilder $container, string $servicePrefix)
    {
        $servicePrefix = rtrim($servicePrefix, '.').'.';

        foreach ($this->resolveSections() as $name => $config) {
            $container->setParameter($servicePrefix.$name.'.host', (string) $config['host']);
            $container->setParameter($servicePrefix.$name.'.host_pattern', (string) ($config['host_pattern'] ?: '.*'));
            $container->setParameter($servicePrefix.$name.'.requirements', $config['requirements']);
            $container->setParameter($servicePrefix.$name.'.defaults', $config['defaults']);
            $container->setParameter($servicePrefix.$name.'.prefix', $config['prefix']);
            $container->setParameter($servicePrefix.$name.'.path', $config['path']);
            $container->register($servicePrefix.$name.'.request_matcher', RequestMatcher::class)->setArguments(
                [
                    $container->getParameterBag()->escapeValue($config['path']),
                    $container->getParameterBag()->escapeValue($config['host_pattern']),
                ]
            );
        }
    }

    /**
     * Returns resolved sections.
     *
     * The returned structure is as follow (value is an associative array):
     * [section-name] => [host, host_pattern, prefix, path]
     *
     * @return array[]
     */
    public function exportConfiguration(): array
    {
        return $this->resolveSections();
    }

    private function resolveSections(): array
    {
        if (null === $this->processed) {
            $this->process();
        }

        $sections = [];

        foreach ($this->processed as $sectionsInHost) {
            foreach ($sectionsInHost as $name => $hostSections) {
                $sections[$name] = $this->generateSectionPath($name, $sectionsInHost);
                unset($sections[$name]['config']);
            }
        }

        return $sections;
    }

    private function groupSectionsPerHost()
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
                if ($config['config']->hostEquals($config2['config'])) {
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
                $this->sections[$primary]['host_pattern'],
                $this->sections[$primary]['prefix'],
                $sections,
            ];
        }

        return $failedSections;
    }

    private function generateSectionPath(string $name, array $allSections): array
    {
        $config = $allSections[$name];
        unset($allSections[$name]);

        $negativePrefixes = [];

        foreach (array_column($allSections, 'prefix') as $prefix) {
            $negativePrefixes[] = $this->findNoneMatchingPath($config['prefix'], $prefix);
        }

        $negativePath = implode('|', array_map('preg_quote', array_filter(array_unique($negativePrefixes))));
        $path = '^/'.preg_quote(ltrim($config['prefix'], '/'), '#');

        if ($negativePath) {
            $path .= "(?!($negativePath)/)";
        }

        $config['path'] = $path;

        return $config;
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
