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

/**
 * @internal
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class SectionConfiguration
{
    public $host;
    public $domain;
    public $prefix;
    public $path;
    public $isSecure = false;
    public $defaults = [];
    public $requirements = [];
    public $hostPattern;

    public function __construct(string $pattern)
    {
        if (!preg_match('%^(?P<schema>https?://)?(?P<host>[^/:]+)?(?P<prefix>(?<!/)/[^$]*)$%is', $pattern, $matches)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Pattern "%s" contains one or more errors. '.
                    'Expected something like: "https://example.com/prefix", "example.com/", "/" or "example.{tld;default;accepted-values}/".',
                    $pattern
                )
            );
        }

        $host = $matches['host'];
        $this->isSecure = 'https://' === $matches['schema'];
        $this->host = $this->processHost($host);
        $this->prefix = $this->processPrefix($matches['prefix']);

        if (null !== $this->host && false === strpos($this->host, '{')) {
            $this->domain = $this->host;
        }

        $route = (new Route(
            $this->prefix,
            $this->defaults,
            $this->requirements,
            [],
            (string) $this->host,
            $this->isSecure ? ['https'] : []
        ))->compile();
        $this->hostPattern = self::stripDelimiters($route->getHostRegex());
    }

    public function hostEquals(self $config): bool
    {
        // A null host will always match anything.
        if (null === $this->host || null === $config->host) {
            return true;
        }

        // Neither has placeholders so we can safely compare them as-is.
        if (!\count($this->requirements) && !\count($config->requirements)) {
            return $this->host === $config->host;
        }

        $hostParts = explode('.', $this->host);
        $hostParts2 = explode('.', $config->host);

        // Different groups are never equal, and can't be checked.
        if (\count($hostParts) !== \count($hostParts2)) {
            return false;
        }

        foreach ($hostParts as $idx => $hostToken) {
            $accepted1 = $this->findAcceptedValues($hostToken, $this);
            $accepted2 = $this->findAcceptedValues($hostParts2[$idx], $config);

            if (!\count(array_intersect($accepted1, $accepted2))) {
                return false;
            }
        }

        // The algorithm was not able to find a positive match so the hosts are equal.
        return true;
    }

    public function toArray()
    {
        return [
            'is_secure' => $this->isSecure,
            'domain' => $this->domain,
            'host' => $this->host,
            'host_pattern' => $this->hostPattern,
            'prefix' => $this->prefix,
            'defaults' => $this->defaults,
            'requirements' => $this->requirements,
            'path' => $this->path,
        ];
    }

    private static function stripDelimiters(?string $regex): ?string
    {
        if (null === $regex || mb_strlen($regex) < 2) {
            return $regex;
        }

        return mb_substr($regex, 1, mb_strrpos($regex, $regex[0], 0, 'UTF-8') - 1, 'UTF-8');
    }

    private function findAcceptedValues(string $tokenPart, self $config): array
    {
        if ('{' === $tokenPart[0]) {
            return explode('|', trim($config->requirements[trim($tokenPart, '{}')], '|'));
        }

        return [$tokenPart];
    }

    private function processHost(string $host): ?string
    {
        // Wildcard character us only used for the pattern. But ignored for the configuration.
        if ('*' === $host || '' === $host) {
            return null;
        }

        if (false !== strpos($host, '{')) {
            $host = preg_replace_callback('#\{([^}]+)\}#', [$this, 'replaceAttributeCallback'], $host);
        }

        return $host;
    }

    /** @internal */
    public function replaceAttributeCallback(array $value): string
    {
        if (!preg_match('#^([a-z]\w*);([\w-]+);([\w-]+(?:\|[\w-]+)*)$#', $value[1], $matches)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid host attribute around "%s". Expected something like: "{name;default;value1|value2}". '.
                    'Accepted values cannot be regexp.',
                    $value[0]
                )
            );
        }
        list(, $name, $default, $accepted) = $matches;

        if (isset($this->defaults[$name])) {
            throw new \InvalidArgumentException(sprintf('Host attribute "%s" is already used.', $name));
        }

        $this->requirements[$name] = $accepted;
        $this->defaults[$name] = $default;

        return '{'.$name.'}';
    }

    private function processPrefix(string $prefix): string
    {
        if (preg_match('#[{}]#', $prefix)) {
            throw new \InvalidArgumentException('Attributes in the "prefix" are not accepted.');
        }

        $prefix = mb_strtolower(trim($prefix, '/'));

        return '' === $prefix ? '/' : "/$prefix/";
    }
}
