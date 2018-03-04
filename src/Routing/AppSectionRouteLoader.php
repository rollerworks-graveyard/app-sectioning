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

namespace Rollerworks\Component\AppSectioning\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * AppSectionRouteLoader loads routing with the section configuration applied.
 *
 * This Loader first loads the actual RouteCollection from the
 * specified resource (anything supported by Symfony) and sets the prefix
 * and host requirements.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class AppSectionRouteLoader extends Loader
{
    /**
     * Regex for matching resource string.
     *
     * Matches:`frontend#something.yml` and `frontend:types#something.yml`.
     */
    private const RESOURCE_REGEX = '/^(?P<section>[a-z-9_-]+)(?::(?P<type>[a-z-9_-]+))?#(?P<resource>[^$]+)$/i';

    private $sections = [];
    private $loader;

    /**
     * Constructor.
     *
     * @param LoaderResolverInterface $loader   Route loader resolver
     * @param array                   $sections Sections as associative array, each entry
     *                                          must contain at least a 'prefix', and optionally
     *                                          host_pattern, host, requirements and defaults
     */
    public function __construct(LoaderResolverInterface $loader, array $sections)
    {
        $this->sections = $sections;
        $this->loader = $loader;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        if (!preg_match(self::RESOURCE_REGEX, $resource, $parts)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'This is not a valid section resource "%s", expected format is "section-name#actual-resource" '.
                    'or "section-name:type#actual-resource".',
                    $resource
                )
            );
        }

        if (!isset($this->sections[$parts['section']])) {
            throw new \InvalidArgumentException(sprintf('No section was registered with name "%s".', $parts['section']));
        }

        if ('app_section' === $parts['type']) {
            throw new \InvalidArgumentException('Unable to import app-section route collection with type "app_section".');
        }

        /** @var RouteCollection $collection */
        $loader = $this->loader->resolve($parts['resource'], '' === (string) $parts['type'] ? null : $parts['type']);
        $collection = $loader->load($parts['resource'], '' === (string) $parts['type'] ? null : $parts['type']);
        $section = $this->sections[$parts['section']];

        // Configure the section information for all imported routes.
        // N.B. this needs to be called 'after' the importing!
        $collection->addPrefix($section['prefix']);

        if (isset($this->sections[$parts['section']]['host'])) {
            $collection->setHost($section['host']);
        }

        $collection->addRequirements($section['requirements'] ?? []);
        $collection->addDefaults($section['defaults'] ?? []);

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'app_section' === $type;
    }
}
