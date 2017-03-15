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

namespace Rollerworks\Bundle\AppSectioning\Tests;

use PHPUnit\Framework\TestCase;
use Rollerworks\Bundle\AppSectioning\SectionConfiguration;
use Rollerworks\Bundle\AppSectioning\SectionsConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RequestMatcher;

final class SectionsConfiguratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_configures_the_paths_by_prefix()
    {
        $configurator = new SectionsConfigurator();
        $configurator->set('frontend', new SectionConfiguration(['prefix' => 'client/']), 'acme.section');
        $configurator->set('backend', new SectionConfiguration(['prefix' => 'backend']), 'acme.section');

        $this->assertEquals(
            [
                'frontend' => ['host' => null, 'host_pattern' => null, 'prefix' => 'client/', 'path' => '^/client/', 'service_prefix' => 'acme.section', 'requirements' => []],
                'backend' => ['host' => null, 'host_pattern' => null, 'prefix' => 'backend/', 'path' => '^/backend/', 'service_prefix' => 'acme.section', 'requirements' => []],
            ],
            $configurator->exportConfiguration()
        );
    }

    /**
     * @test
     */
    public function it_configures_the_host_pattern_by_host()
    {
        $configurator = new SectionsConfigurator();
        $configurator->set('frontend', new SectionConfiguration(['prefix' => '/', 'host' => 'example.net']), 'acme.section');
        $configurator->set('backend', new SectionConfiguration(['prefix' => '/', 'host' => 'example.com']), 'acme.section');

        $this->assertEquals(
            [
                'frontend' => [
                    'host' => 'example.net',
                    'host_pattern' => '^example\.net$',
                    'prefix' => '/',
                    'path' => '^/',
                    'service_prefix' => 'acme.section',
                    'requirements' => ['host' => '^example\.net$'],
                ],
                'backend' => [
                    'host' => 'example.com',
                    'host_pattern' => '^example\.com$',
                    'prefix' => '/',
                    'path' => '^/',
                    'service_prefix' => 'acme.section',
                    'requirements' => ['host' => '^example\.com$'],
                ],
            ],
            $configurator->exportConfiguration()
        );
    }

    /**
     * @test
     */
    public function its_configured_path_excludes_other_paths()
    {
        $configurator = new SectionsConfigurator();
        $configurator->set('frontend', new SectionConfiguration(['prefix' => '/']), 'acme.section');
        $configurator->set('backend', new SectionConfiguration(['prefix' => 'backend']), 'acme.section');
        $configurator->set('api', new SectionConfiguration(['prefix' => 'api']), 'acme.section');

        $this->assertEquals(
            [
                'frontend' => [
                    'host' => null,
                    'host_pattern' => null,
                    'prefix' => '/',
                    'path' => '^/(?!(backend|api)/)',
                    'service_prefix' => 'acme.section',
                    'requirements' => [],
                ],
                'backend' => [
                    'host' => null,
                    'host_pattern' => null,
                    'prefix' => 'backend/',
                    'path' => '^/backend/',
                    'service_prefix' => 'acme.section',
                    'requirements' => [],
                ],
                'api' => [
                    'host' => null,
                    'host_pattern' => null,
                    'prefix' => 'api/',
                    'path' => '^/api/',
                    'service_prefix' => 'acme.section',
                    'requirements' => [],
                ],
            ],
            $configurator->exportConfiguration()
        );
    }

    /**
     * @test
     */
    public function its_configured_path_excludes_other_sub_paths()
    {
        $configurator = new SectionsConfigurator();
        $configurator->set('frontend', new SectionConfiguration(['prefix' => '/']), 'acme.section');
        $configurator->set('backend', new SectionConfiguration(['prefix' => 'backend']), 'acme.section');
        $configurator->set('backend_api', new SectionConfiguration(['prefix' => 'api/backend']), 'acme.section');
        $configurator->set('api', new SectionConfiguration(['prefix' => 'api']), 'acme.section');

        $this->assertEquals(
            [
                'frontend' => [
                    'host' => null,
                    'host_pattern' => null,
                    'prefix' => '/',
                    'path' => '^/(?!(backend|api)/)',
                    'service_prefix' => 'acme.section',
                    'requirements' => [],
                ],
                'backend' => [
                    'host' => null,
                    'host_pattern' => null,
                    'prefix' => 'backend/',
                    'path' => '^/backend/',
                    'service_prefix' => 'acme.section',
                    'requirements' => [],
                ],
                'backend_api' => [
                    'host' => null,
                    'host_pattern' => null,
                    'prefix' => 'api/backend/',
                    'path' => '^/api/backend/',
                    'service_prefix' => 'acme.section',
                    'requirements' => [],
                ],
                'api' => [
                    'host' => null,
                    'host_pattern' => null,
                    'prefix' => 'api/',
                    'path' => '^/api/(?!(backend)/)',
                    'service_prefix' => 'acme.section',
                    'requirements' => [],
                ],
            ],
            $configurator->exportConfiguration()
        );
    }

    /**
     * @test
     */
    public function its_configured_path_excludes_other_sub_paths_in_host()
    {
        $configurator = new SectionsConfigurator();
        $configurator->set('frontend', new SectionConfiguration(['prefix' => '/', 'host' => 'example.com']), 'acme.section');
        $configurator->set('backend', new SectionConfiguration(['prefix' => 'backend', 'host' => 'example.com']), 'acme.section');
        $configurator->set('backend_api', new SectionConfiguration(['prefix' => 'api/backend', 'host' => 'example.com']), 'acme.section');
        $configurator->set('api', new SectionConfiguration(['prefix' => 'api', 'host' => 'example.com']), 'acme.section');

        $this->assertEquals(
            [
                'frontend' => [
                    'host' => 'example.com',
                    'host_pattern' => '^example\.com$',
                    'prefix' => '/',
                    'path' => '^/(?!(backend|api)/)',
                    'service_prefix' => 'acme.section',
                    'requirements' => ['host' => '^example\.com$'],
                ],
                'backend' => [
                    'host' => 'example.com',
                    'host_pattern' => '^example\.com$',
                    'prefix' => 'backend/',
                    'path' => '^/backend/',
                    'service_prefix' => 'acme.section',
                    'requirements' => ['host' => '^example\.com$'],
                ],
                'backend_api' => [
                    'host' => 'example.com',
                    'host_pattern' => '^example\.com$',
                    'prefix' => 'api/backend/',
                    'path' => '^/api/backend/',
                    'service_prefix' => 'acme.section',
                    'requirements' => ['host' => '^example\.com$'],
                ],
                'api' => [
                    'host' => 'example.com',
                    'host_pattern' => '^example\.com$',
                    'prefix' => 'api/',
                    'path' => '^/api/(?!(backend)/)',
                    'service_prefix' => 'acme.section',
                    'requirements' => ['host' => '^example\.com$'],
                ],
            ],
            $configurator->exportConfiguration()
        );
    }

    /**
     * @test
     */
    public function it_registers_in_the_container()
    {
        $container = new ContainerBuilder();

        $configurator = new SectionsConfigurator();
        $configurator->set('frontend', new SectionConfiguration(['prefix' => '/', 'host' => 'example.com']), 'acme.section');
        $configurator->set('backend', new SectionConfiguration(['prefix' => 'backend', 'host' => 'example.com']), 'park.section');

        $configurator->registerToContainer($container);

        $this->assertTrue($container->hasParameter('acme.section.frontend.host'));
        $this->assertTrue($container->hasParameter('acme.section.frontend.host_pattern'));
        $this->assertTrue($container->hasParameter('acme.section.frontend.prefix'));
        $this->assertTrue($container->hasParameter('acme.section.frontend.path'));
        $this->assertTrue($container->hasDefinition('acme.section.frontend.request_matcher'));

        $this->assertTrue($container->hasParameter('park.section.backend.host'));
        $this->assertTrue($container->hasParameter('park.section.backend.host_pattern'));
        $this->assertTrue($container->hasParameter('park.section.backend.prefix'));
        $this->assertTrue($container->hasParameter('park.section.backend.path'));
        $this->assertTrue($container->hasDefinition('park.section.backend.request_matcher'));

        $this->assertEquals('example.com', $container->getParameter('acme.section.frontend.host'));
        $this->assertEquals('^example\.com$', $container->getParameter('acme.section.frontend.host_pattern'));
        $this->assertEquals('/', $container->getParameter('acme.section.frontend.prefix'));
        $this->assertEquals('^/(?!(backend)/)', $container->getParameter('acme.section.frontend.path'));

        $this->assertEquals('example.com', $container->getParameter('park.section.backend.host'));
        $this->assertEquals('^example\.com$', $container->getParameter('park.section.backend.host_pattern'));
        $this->assertEquals('backend/', $container->getParameter('park.section.backend.prefix'));
        $this->assertEquals('^/backend/', $container->getParameter('park.section.backend.path'));

        $requestMatcherFrontend = $container->getDefinition('acme.section.frontend.request_matcher');
        $this->assertEquals(RequestMatcher::class, $requestMatcherFrontend->getClass());
        $this->assertEquals(['^/(?!(backend)/)', '^example\.com$'], $requestMatcherFrontend->getArguments());

        $requestMatcherBackend = $container->getDefinition('park.section.backend.request_matcher');
        $this->assertEquals(RequestMatcher::class, $requestMatcherBackend->getClass());
        $this->assertEquals(['^/backend/', '^example\.com$'], $requestMatcherBackend->getArguments());

        // Ensure definitions are correct.
        $container->compile();
    }
}
