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

namespace Rollerworks\Component\AppSectioning\Tests;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractContainerBuilderTestCase;
use Rollerworks\Component\AppSectioning\Routing\AppSectionRouteLoader;
use Rollerworks\Component\AppSectioning\SectioningFactory;

final class SectioningFactoryTest extends AbstractContainerBuilderTestCase
{
    /**
     * @test
     */
    public function it_registers_sections_in_the_container()
    {
        $factory = new SectioningFactory($this->container, 'acme.section');
        $factory->set('frontend', ['prefix' => '/', 'host' => 'example.com']);
        $factory->set('backend', ['prefix' => 'backend', 'host' => 'example.com']);
        $factory->register();

        $this->assertContainerBuilderHasService('rollerworks.app_section.route_loader', AppSectionRouteLoader::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'rollerworks.app_section.route_loader',
            1,
            [
                'frontend' => [
                    'host' => 'example.com',
                    'defaults' => [],
                    'requirements' => [],
                    'prefix' => '/',
                    'host_pattern' => '^example\.com$',
                    'path' => '^/(?!(backend)/)',
                ],
                'backend' => [
                    'host' => 'example.com',
                    'defaults' => [],
                    'requirements' => [],
                    'prefix' => 'backend/',
                    'host_pattern' => '^example\.com$',
                    'path' => '^/backend/',
                ],
            ]
        );

        // Ensure definitions are correct.
        $this->compile();
    }

    /**
     * @test
     */
    public function it_registers_sections_from_array()
    {
        $factory = new SectioningFactory($this->container, 'acme.section');
        $factory->fromArray(['frontend', 'backend'], [
            'frontend' => ['prefix' => '/', 'host' => 'example.com'],
            'backend' => ['prefix' => 'backend', 'host' => 'example.com'],
        ]);
        $factory->register();

        $this->assertContainerBuilderHasService('rollerworks.app_section.route_loader', AppSectionRouteLoader::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'rollerworks.app_section.route_loader',
            1,
            [
                'frontend' => [
                    'host' => 'example.com',
                    'defaults' => [],
                    'requirements' => [],
                    'prefix' => '/',
                    'host_pattern' => '^example\.com$',
                    'path' => '^/(?!(backend)/)',
                ],
                'backend' => [
                    'host' => 'example.com',
                    'defaults' => [],
                    'requirements' => [],
                    'prefix' => 'backend/',
                    'host_pattern' => '^example\.com$',
                    'path' => '^/backend/',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_registers_sections_from_json()
    {
        $factory = new SectioningFactory($this->container, 'acme.section');
        $factory->fromJson(['frontend', 'backend'], json_encode([
            'frontend' => ['prefix' => '/', 'host' => 'example.com'],
            'backend' => ['prefix' => 'backend', 'host' => 'example.com'],
        ]));
        $factory->register();

        $this->assertContainerBuilderHasService('rollerworks.app_section.route_loader', AppSectionRouteLoader::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'rollerworks.app_section.route_loader',
            1,
            [
                'frontend' => [
                    'host' => 'example.com',
                    'defaults' => [],
                    'requirements' => [],
                    'prefix' => '/',
                    'host_pattern' => '^example\.com$',
                    'path' => '^/(?!(backend)/)',
                ],
                'backend' => [
                    'host' => 'example.com',
                    'defaults' => [],
                    'requirements' => [],
                    'prefix' => 'backend/',
                    'host_pattern' => '^example\.com$',
                    'path' => '^/backend/',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_validates_register_by_array_for_required_sections()
    {
        $factory = new SectioningFactory($this->container, 'acme.section');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The following AppSections are required but were not set: backend, api');

        $factory->fromArray(['frontend', 'backend', 'api'], [
            'frontend' => ['prefix' => '/', 'host' => 'example.com'],
        ]);
    }

    /**
     * @test
     */
    public function it_validates_register_by_array_for_correct_structure()
    {
        $factory = new SectioningFactory($this->container, 'acme.section');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('AppSection "frontend" configuration expects an array got NULL instead.');

        $factory->fromArray(['frontend'], [
            'frontend' => null,
        ]);
    }

    /**
     * @test
     */
    public function it_validates_register_by_array_for_correct_syntax()
    {
        $factory = new SectioningFactory($this->container, 'acme.section');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('AppSections configuration is invalid. Message: Syntax error');

        $factory->fromJson(['frontend', 'backend', 'api'], '[');
    }

    /**
     * @test
     */
    public function it_validates_register_by_array_for_correct_input()
    {
        $factory = new SectioningFactory($this->container, 'acme.section');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('AppSections configuration is expected to be an array.');

        $factory->fromJson(['frontend', 'backend', 'api'], '"Nope"');
    }

    /**
     * @test
     */
    public function it_informs_errors_()
    {
        $factory = new SectioningFactory($this->container, 'acme.section');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('AppSection "frontend" configuration is invalid: Missing requirement for attribute "com".');

        $factory->set('frontend', ['prefix' => '/', 'host' => 'example.{com}']);
    }
}
