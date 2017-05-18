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

namespace Rollerworks\Bundle\AppSectioningBundle;

use Symfony\Component\Routing\Route;

/** @internal */
final class SectionConfiguration
{
    private $config;

    public function __construct(array $config)
    {
        $this->validateInputConfig($config);

        $config = array_merge(
            [
                'host' => null,
                'defaults' => [],
                'requirements' => [],
            ],
            $config
        );

        $config['prefix'] = $this->normalizePrefix($config['prefix']);
        $route = (new Route($config['prefix'], $config['defaults'], $config['requirements'], [], $config['host']))->compile();
        $config['host_pattern'] = self::stripDelimiters($route->getHostRegex());

        $this->config = $config;
    }

    /**
     * @return array [prefix, host, host_pattern, defaults, requirements]
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

        if (preg_match('#[{}]#', (string) $config['prefix'])) {
            throw new \InvalidArgumentException(
                'Placeholders in the "prefix" are not supported.'
            );
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

    private static function stripDelimiters(?string $regex)
    {
        if (null === $regex || mb_strlen($regex) < 2) {
            return $regex;
        }

        $delimiter = $regex[0];

        if ($regex[0] === '{') {
            $delimiter = '}';
        }

        return mb_substr($regex, 1, mb_strrpos($regex, $delimiter, 0, 'UTF-8') - 1, 'UTF-8');
    }
}
