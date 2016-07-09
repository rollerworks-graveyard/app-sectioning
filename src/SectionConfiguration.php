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

final class SectionConfiguration
{
    private $config;

    public function __construct(array $config)
    {
        $this->validateInputConfig($config);

        $config['prefix'] = $this->normalizePrefix($config['prefix']);

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

    private function validateInputConfig(array $config)
    {
        if (!isset($config['prefix']) || '' === $config['prefix']) {
            throw new \InvalidArgumentException('AppSection prefix cannot be empty. Use at least "/".');
        }
    }

    private function normalizePrefix(string $prefix)
    {
        $prefix = trim(mb_strtolower($prefix), '/');

        if ('/' !== $prefix) {
            $prefix .= '/';
        }

        return $prefix;
    }
}
