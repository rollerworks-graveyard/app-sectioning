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

use Rollerworks\Component\AppSectioning\Routing\AppSectionRouteLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * SectioningFactory registers the sections and routing service.
 *
 * Example:
 * (new SectioningFactory($container, 'acme.sections'))
 *     ->set('section-name', ['configuration-as-provided-by-SectioningConfigurator'])
 *     ->set('section-name2', ['configuration-as-provided-by-SectioningConfigurator'])
 *     ->register();
 *
 * Example ():
 * (new SectioningFactory($container, 'acme.sections'))
 *     ->fromArray(['section1', 'section2'], $container->getParameter('app_sections'))
 *     // fromJson(['section1', 'section2'], $_ENV['app_sections'] ?? '')
 *     ->register();
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class SectioningFactory
{
    public const TAG_NAME = 'rollerworks.app_section';

    private $container;
    private $servicePrefix;

    /**
     * @var SectionConfiguration[]
     */
    private $sections = [];

    /**
     * @param ContainerBuilder $container
     * @param string           $servicePrefix Prefix to use for registered services
     */
    public function __construct(ContainerBuilder $container, string $servicePrefix)
    {
        $this->container = $container;
        $this->servicePrefix = $servicePrefix;
    }

    /**
     * Set the configuration for a section.
     *
     * @param string $name   Name of the section, must be unique within the service-prefix
     * @param array  $config Configuration of the section, must contain a 'prefix' key
     *
     * @return SectioningFactory Fluent interface
     */
    public function set(string $name, array $config): self
    {
        try {
            $this->sections[$name] = new SectionConfiguration($config);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException(
                sprintf('AppSection "%s" configuration is invalid: %s', $name, $e->getMessage()), 0, $e
            );
        }

        return $this;
    }

    public function register(): void
    {
        $configurator = new SectionsConfigurator();
        foreach ($this->sections as $name => $config) {
            $configurator->set($name, $config);
        }

        $configurator->registerToContainer($this->container, $this->servicePrefix);
        $this->container->register('rollerworks.app_section.route_loader', AppSectionRouteLoader::class)
            ->setArguments([new Reference('routing.resolver'), $configurator->exportConfiguration()])
            ->addTag('routing.loader');
    }

    /**
     * @throws \InvalidArgumentException When the configuration is invalid
     */
    public function fromArray(array $required, array $sections): self
    {
        $missingKeys = [];

        foreach ($required as $key) {
            if (!array_key_exists($key, $sections)) {
                $missingKeys[] = $key;
            } elseif (!\is_array($sections[$key])) {
                throw new \InvalidArgumentException(sprintf('AppSection "%s" configuration expects an array got %s instead.', $key, gettype($sections[$key])));
            } else {
                $this->set($key, $sections[$key]);
            }
        }

        if ($missingKeys) {
            throw new \InvalidArgumentException(sprintf('The following AppSections are required but were not set: %s', implode(', ', $missingKeys)));
        }

        return $this;
    }

    public function fromJson(array $required, string $value): self
    {
        $sections = json_decode($value, true, 512, JSON_BIGINT_AS_STRING);

        if (null === $sections) {
            throw new \InvalidArgumentException(sprintf('AppSections configuration is invalid. Message: %s', json_last_error_msg()));
        }

        if (!\is_array($sections)) {
            throw new \InvalidArgumentException('AppSections configuration is expected to be an array.');
        }

        $this->fromArray($required, $sections);

        return $this;
    }
}
