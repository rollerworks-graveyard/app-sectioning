<?php

/*
 * This file is part of the Rollerworks AppSectioningBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\AppSectioning\DependencyInjection;

use Rollerworks\Bundle\AppSectioning\SectionConfiguration;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * SectioningFactory registers the sections for later validation and processing.
 *
 * The late processing allows to use ServiceContainer parameters and combining
 * sections from other bundles.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class SectioningFactory
{
    const TAG_NAME = 'park_manager.app_section';

    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var string
     */
    private $servicePrefix;

    /**
     * Constructor.
     *
     * @param ContainerBuilder $container     Service container builder.
     * @param string           $servicePrefix Prefix to use for registered services.
     */
    public function __construct(ContainerBuilder $container, string $servicePrefix)
    {
        $this->container = $container;
        $this->servicePrefix = trim($servicePrefix, '.\\');
    }

    /**
     * Set the configuration for a section.
     *
     * @param string $name   Name of the section, must be unique within the service-prefix.
     * @param array  $config Configuration of the section, must contain a 'prefix' key.
     *
     * @return SectioningFactory Fluent interface.
     */
    public function set(string $name, array $config): SectioningFactory
    {
        $sectionDef = new Definition(SectionConfiguration::class);
        $sectionDef->setPublic(false);
        $sectionDef->addArgument($config);
        $sectionDef->addTag(
            self::TAG_NAME,
            ['service_prefix' => $this->servicePrefix, 'section_name' => $name]
        );

        $this->container->setDefinition(
            sprintf('park_manager.app_section.%s.%s', $this->servicePrefix, $name),
            $sectionDef
        );

        return $this;
    }
}
