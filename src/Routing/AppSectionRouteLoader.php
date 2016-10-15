<?php

/*
 * This file is part of the Rollerworks AppSectioningBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\AppSectioning\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Config\Loader\LoaderInterface;
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
    const RESOURCE_REGEX = '/^(?P<section>[a-z-9_-]+)(?::(?P<type>[a-z-9_-]+))?#(?P<resource>[^$]+)$/i';

    private $sections = [];
    private $loader;

    /**
     * Constructor.
     *
     * @param LoaderInterface $loader   Route loader
     * @param array           $sections Sections as associative array, each entry
     *                                  must contain at least a 'prefix', and optionally
     *                                  host_pattern, host, host_requirements
     */
    public function __construct(LoaderInterface $loader, array $sections)
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
        $collection = $this->loader->load($parts['resource'], '' === $parts['type'] ? null : $parts['type']);

        // Configure the section information for all imported routes.
        // N.B. this needs to be called 'after' the importing!
        $collection->addPrefix($this->sections[$parts['section']]['prefix']);

        if (isset($this->sections[$parts['section']]['host'])) {
            $section = $this->sections[$parts['section']];

            $collection->setHost('{host}', ['host' => $section['host']]);
            $collection->addRequirements($section['requirements']);
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return $type === 'app_section';
    }
}
