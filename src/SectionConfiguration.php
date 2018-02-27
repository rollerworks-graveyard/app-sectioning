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

use Symfony\Component\Routing\Route;

/** @internal */
final class SectionConfiguration
{
    private $config;

    public function __construct(array $config)
    {
        $config = array_merge(
            [
                'prefix' => '',
                'host' => null,
                'defaults' => [],
                'requirements' => [],
            ],
            $config
        );
        $this->validateInputConfig($config);

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

    public function hostEquals(self $config): bool
    {
        // A null host will always match anything.
        if (null === $this->config['host'] || null === $config->config['host']) {
            return true;
        }

        // Neither has placeholders so we can safely compare them as-is.
        if (!\count($this->config['requirements']) && !\count($config->config['requirements'])) {
            return $this->config['host'] === $config->config['host'];
        }

        $hostParts = explode('.', $this->config['host']);
        $hostParts2 = explode('.', $config->config['host']);

        // Different groups are never equal, and can't be checked.
        if (\count($hostParts) !== \count($hostParts2)) {
            return false;
        }

        foreach ($hostParts as $idx => $hostToken) {
            $accepted1 = $this->findAcceptedValues($hostToken, $this->config);
            $accepted2 = $this->findAcceptedValues($hostParts2[$idx], $config->config);

            if (!\count(array_intersect($accepted1, $accepted2))) {
                return false;
            }
        }

        // The algorithm was not able to find a positive match so the hosts are equal.
        return true;
    }

    private function validateInputConfig(array $config)
    {
        if (!is_array($config['requirements']) || !is_array($config['defaults'])) {
            throw new \InvalidArgumentException('Keys "requirements" and "default" must be arrays or absent.');
        }

        if (!is_string($config['prefix']) || '' === trim($config['prefix'])) {
            throw new \InvalidArgumentException('Prefix must be an string and cannot be empty. Use at least "/".');
        }

        if (preg_match('#[{}]#', $config['prefix'])) {
            throw new \InvalidArgumentException('Placeholders in the "prefix" are not accepted.');
        }

        if (false !== strpos((string) $config['host'], '{')) {
            $varNames = [];

            preg_match_all('#\{\w+\}#', $config['host'], $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
            foreach ($matches as $match) {
                $varNames[] = substr($match[0][0], 1, -1);
            }

            foreach ($varNames as $varName) {
                if (!isset($config['requirements'][$varName])) {
                    throw new \InvalidArgumentException(sprintf('Missing requirement for attribute "%s".', $varName));
                }

                if (!isset($config['defaults'][$varName]) || '' === trim((string) $config['defaults'][$varName])) {
                    throw new \InvalidArgumentException(sprintf('Missing default value for attribute "%s".', $varName));
                }

                if (!preg_match('/^([\p{L}\p{N}-_]+\|?)+$/iu', (string) $config['requirements'][$varName])) {
                    throw new \InvalidArgumentException(
                        'A host requirement can only hold letters, numbers with middle and underscores'.
                        ' separated by "|" (to allow more combinations).'
                    );
                }
            }
        }
    }

    private function normalizePrefix(string $prefix): string
    {
        $prefix = trim(mb_strtolower($prefix), '/');

        if ('/' !== $prefix) {
            $prefix .= '/';
        }

        return $prefix;
    }

    private static function stripDelimiters(?string $regex): ?string
    {
        if (null === $regex || mb_strlen($regex) < 2) {
            return $regex;
        }

        return mb_substr($regex, 1, mb_strrpos($regex, $regex[0], 0, 'UTF-8') - 1, 'UTF-8');
    }

    private function findAcceptedValues(string $tokenPart, array $config): array
    {
        if ('{' === $tokenPart[0]) {
            return explode('|', trim($config['requirements'][trim($tokenPart, '{}')], '|'));
        }

        return [$tokenPart];
    }
}
