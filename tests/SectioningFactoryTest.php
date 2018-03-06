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
        $factory->set('frontend', 'example.com/');
        $factory->set('backend', 'https://example.com/backend');
        $factory->register();

        $this->assertContainerBuilderHasService('rollerworks.app_section.route_loader', AppSectionRouteLoader::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'rollerworks.app_section.route_loader',
            1,
            [
                'frontend' => [
                    'is_secure' => false,
                    'domain' => 'example.com',
                    'host' => 'example.com',
                    'host_pattern' => '^example\.com$',
                    'prefix' => '/',
                    'defaults' => [],
                    'requirements' => [],
                    'path' => '^/(?!(backend)/)',
                ],
                'backend' => [
                    'is_secure' => true,
                    'domain' => 'example.com',
                    'host' => 'example.com',
                    'host_pattern' => '^example\.com$',
                    'prefix' => 'backend/',
                    'defaults' => [],
                    'requirements' => [],
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
    public function it_informs_errors()
    {
        $factory = new SectioningFactory($this->container, 'acme.section');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('AppSection "frontend" configuration is invalid: ');

        $factory->set('frontend', 'https://');
    }
}
