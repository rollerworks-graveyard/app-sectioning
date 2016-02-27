<?php

/*
 * This file is part of the ParkManager AppSectioning package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ParkManager\Component\AppSectioning;

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
 */
final class SectionsConfigurator
{
    /**
     * @var array[]
     */
    private $sections = [];

    const DEFAULT_VALUES = [
        'prefix' => '/',
        'host' => null,
        'host_pattern' => null,
    ];

    /**
     * Set a section to be processed.
     *
     * @param string $name
     * @param array  $config
     */
    public function set(string $name, array $config)
    {
        if (isset($config['prefix'])) {
            $config['prefix'] = trim(mb_strtolower($config['prefix']), '/').'/';
        }

        if (isset($config['host']) && '' !== (string) $config['host']) {
            $config['host'] = mb_strtolower($config['host']);

            if (empty($primaryConfig['host_pattern'])) {
                $config['host_pattern'] = '^'.preg_quote($config['host']).'$';
            }
        }

        $this->sections[$name] = array_merge(self::DEFAULT_VALUES, $config);
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
     * @param ContainerBuilder $container
     * @param string           $servicePrefix
     */
    public function registerToContainer(ContainerBuilder $container, string $servicePrefix)
    {
        $servicePrefix = rtrim($servicePrefix, '.').'.';

        foreach ($this->processSections($this->sections) as $name => $config) {
            $container->setParameter($servicePrefix.$name.'.host', $config['host']);
            $container->setParameter($servicePrefix.$name.'.host_pattern', $config['host_pattern']);
            $container->setParameter($servicePrefix.$name.'.prefix', $config['prefix']);
            $container->setParameter($servicePrefix.$name.'.path', $config['path']);
            $container->register($servicePrefix.$name.'.request_matcher', RequestMatcher::class)->setArguments(
                ['%'.$servicePrefix.$name.'.path%', '%'.$servicePrefix.$name.'.host_pattern%']
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
        return $this->processSections($this->sections);
    }

    private function processSections(array $sections): array
    {
        $hostsSections = [];

        // First step is to group sections per host
        foreach ($sections as $name => $config) {
            $hostsSections[$config['host']][$name] = $config;
        }

        $sections = [];

        // Now process each section in a group to ensure they are (auto) configured.
        foreach ($hostsSections as $sectionsInHost) {
            foreach ($sectionsInHost as $name => $hostSections) {
                $sections[$name] = $this->setSectionPath($name, $sectionsInHost);
            }
        }

        return $sections;
    }

    private function setSectionPath(string $name, array $allSections): array
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

        // Start index is non-existing, so the prefix is '/', and thus will always match.
        // When $prefixes[0] is unset it means there is logic error, only one '/' is allowed per host.
        if (!isset($prefix[0])) {
            return $prefixes[0];
        }

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