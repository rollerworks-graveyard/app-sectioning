<?php

/*
 * This file is part of the Park-Manager AppSectioningBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ParkManager\Bundle\AppSectioning;

/**
 * The SectionConfiguration holds the configuration for a single app section.
 */
final class SectionConfiguration
{
    private $config;

    public function __construct(array $config)
    {
        if (!isset($config['prefix']) || '' === $config['prefix']) {
            throw new \InvalidArgumentException('AppSection prefix cannot be empty. Use at least "/".');
        }

        $config['prefix'] = trim(mb_strtolower($config['prefix']), '/');

        if ('/' !== $config['prefix']) {
            $config['prefix'] .= '/';
        }

        if (isset($config['host']) && '' !== (string) $config['host']) {
            $config['host'] = mb_strtolower($config['host']);
            $config['host_pattern'] = '^'.preg_quote($config['host']).'$';
            $config['requirements']['host'] = $config['host_pattern'];
        } else {
            $config['host'] = null;
            $config['host_pattern'] = null;
            $config['requirements'] = [];
        }

        if (preg_match('#[{}]#', (string) $config['host']) || preg_match('#[{}]#', (string) $config['prefix'])) {
            throw new \InvalidArgumentException(
                'Placeholders in the "host" and/or "prefix" are not supported yet.'
            );
        }

        $this->config = $config;
    }

    /**
     * @return array [prefix, host]
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
