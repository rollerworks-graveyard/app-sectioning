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

namespace Rollerworks\Bundle\AppSectioning;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RequestMatcher;

/**
 * SectionsConfigurator registers the resolved sections configuration
 * in the service-container.
 *
 * The main purpose of this class is to auto configure the path of a section without conflict.
 * Say there are two sections:
 *
 * * Frontend - host: example.com prefix: /
 * * Backend  - host: example.com prefix: backend/
 *
 * Unless the 'backend' section is tried earlier the 'frontend' will always match!
 * To prevent this the path (regex) is configured to never match 'backend/'.
 * Only when both share the same host and only there is an actual conflict.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class SectionsConfigurator
{
    private $sections = [];

    public function set(string $name, SectionConfiguration $config, string $servicePrefix)
    {
        $this->sections[$name] = $config->getConfig();
        $this->sections[$name]['service_prefix'] = $servicePrefix;
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
    public function registerToContainer(ContainerBuilder $container)
    {
        foreach ($this->resolveSections($this->sections) as $name => $config) {
            $servicePrefix = rtrim($config['service_prefix'], '.').'.';

            $container->setParameter($servicePrefix.$name.'.host', (string) $config['host']);
            $container->setParameter($servicePrefix.$name.'.host_pattern', (string) ($config['host_pattern'] ?: '.*'));
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
     * The returned structure is like follow (value is an associative array):
     * [section-name] => [host, host_pattern, prefix, path]
     *
     * @return array[]
     */
    public function exportConfiguration(): array
    {
        return $this->resolveSections($this->sections);
    }

    private function resolveSections(array $sections): array
    {
        $hostsSections = $this->groupSectionsPerHost($sections);

        foreach ($hostsSections as $sectionsInHost) {
            foreach ($sectionsInHost as $name => $hostSections) {
                $sections[$name] = $this->generateSectionPath($name, $sectionsInHost);
            }
        }

        return $sections;
    }

    private function groupSectionsPerHost(array $sections)
    {
        $hostsSections = [];

        foreach ($sections as $name => $config) {
            $hostsSections[$config['host']][$name] = $config;
        }

        return $hostsSections;
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
        $path = '^/'.preg_quote(ltrim($config['prefix'], '/'));

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
