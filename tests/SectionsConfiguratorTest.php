<?php

/*
 * This file is part of the ParkManager AppSectioning package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ParkManager\Bundle\AppSectioning\Tests;

use ParkManager\Bundle\AppSectioning\SectionsConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RequestMatcher;

final class SectionsConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_configures_the_paths_by_prefix()
    {
        $configurator = new SectionsConfigurator();
        $configurator->set('frontend', ['prefix' => 'client/']);
        $configurator->set('backend', ['prefix' => 'backend']);

        $this->assertEquals(
            [
                'frontend' => ['host' => null, 'host_pattern' => null, 'prefix' => 'client/', 'path' => '^/client/'],
                'backend' => ['host' => null, 'host_pattern' => null, 'prefix' => 'backend/', 'path' => '^/backend/'],
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
        $configurator->set('frontend', ['prefix' => '/', 'host' => 'example.net']);
        $configurator->set('backend', ['prefix' => '/', 'host' => 'example.com']);

        $this->assertEquals(
            [
                'frontend' => [
                    'host' => 'example.net',
                    'host_pattern' => '^example\.net$',
                    'prefix' => '/',
                    'path' => '^/',
                ],
                'backend' => [
                    'host' => 'example.com',
                    'host_pattern' => '^example\.com$',
                    'prefix' => '/',
                    'path' => '^/',
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
        $configurator->set('frontend', ['prefix' => '/']);
        $configurator->set('backend', ['prefix' => 'backend']);
        $configurator->set('api', ['prefix' => 'api']);

        $this->assertEquals(
            [
                'frontend' => [
                    'host' => null,
                    'host_pattern' => null,
                    'prefix' => '/',
                    'path' => '^/(?!(backend|api)/)',
                ],
                'backend' => [
                    'host' => null,
                    'host_pattern' => null,
                    'prefix' => 'backend/',
                    'path' => '^/backend/',
                ],
                'api' => [
                    'host' => null,
                    'host_pattern' => null,
                    'prefix' => 'api/',
                    'path' => '^/api/',
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
        $configurator->set('frontend', ['prefix' => '/']);
        $configurator->set('backend', ['prefix' => 'backend']);
        $configurator->set('backend_api', ['prefix' => 'api/backend']);
        $configurator->set('api', ['prefix' => 'api']);

        $this->assertEquals(
            [
                'frontend' => [
                    'host' => null,
                    'host_pattern' => null,
                    'prefix' => '/',
                    'path' => '^/(?!(backend|api)/)',
                ],
                'backend' => [
                    'host' => null,
                    'host_pattern' => null,
                    'prefix' => 'backend/',
                    'path' => '^/backend/',
                ],
                'backend_api' => [
                    'host' => null,
                    'host_pattern' => null,
                    'prefix' => 'api/backend/',
                    'path' => '^/api/backend/',
                ],
                'api' => [
                    'host' => null,
                    'host_pattern' => null,
                    'prefix' => 'api/',
                    'path' => '^/api/(?!(backend)/)',
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
        $configurator->set('frontend', ['prefix' => '/', 'host' => 'example.com']);
        $configurator->set('backend', ['prefix' => 'backend', 'host' => 'example.com']);
        $configurator->set('backend_api', ['prefix' => 'api/backend', 'host' => 'example.com']);
        $configurator->set('api', ['prefix' => 'api', 'host' => 'example.com']);

        $this->assertEquals(
            [
                'frontend' => [
                    'host' => 'example.com',
                    'host_pattern' => '^example\.com$',
                    'prefix' => '/',
                    'path' => '^/(?!(backend|api)/)',
                ],
                'backend' => [
                    'host' => 'example.com',
                    'host_pattern' => '^example\.com$',
                    'prefix' => 'backend/',
                    'path' => '^/backend/',
                ],
                'backend_api' => [
                    'host' => 'example.com',
                    'host_pattern' => '^example\.com$',
                    'prefix' => 'api/backend/',
                    'path' => '^/api/backend/',
                ],
                'api' => [
                    'host' => 'example.com',
                    'host_pattern' => '^example\.com$',
                    'prefix' => 'api/',
                    'path' => '^/api/(?!(backend)/)',
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
        $configurator->set('frontend', ['prefix' => '/', 'host' => 'example.com']);
        $configurator->set('backend', ['prefix' => 'backend', 'host' => 'example.com']);

        $configurator->registerToContainer($container, 'acme.section');

        $this->assertTrue($container->hasParameter('acme.section.frontend.host'));
        $this->assertTrue($container->hasParameter('acme.section.frontend.host_pattern'));
        $this->assertTrue($container->hasParameter('acme.section.frontend.prefix'));
        $this->assertTrue($container->hasParameter('acme.section.frontend.path'));
        $this->assertTrue($container->hasDefinition('acme.section.frontend.request_matcher'));

        $this->assertTrue($container->hasParameter('acme.section.backend.host'));
        $this->assertTrue($container->hasParameter('acme.section.backend.host_pattern'));
        $this->assertTrue($container->hasParameter('acme.section.backend.prefix'));
        $this->assertTrue($container->hasParameter('acme.section.backend.path'));
        $this->assertTrue($container->hasDefinition('acme.section.backend.request_matcher'));

        $this->assertEquals('example.com', $container->getParameter('acme.section.frontend.host'));
        $this->assertEquals('^example\.com$', $container->getParameter('acme.section.frontend.host_pattern'));
        $this->assertEquals('/', $container->getParameter('acme.section.frontend.prefix'));
        $this->assertEquals('^/(?!(backend)/)', $container->getParameter('acme.section.frontend.path'));

        $this->assertEquals('example.com', $container->getParameter('acme.section.backend.host'));
        $this->assertEquals('^example\.com$', $container->getParameter('acme.section.backend.host_pattern'));
        $this->assertEquals('backend/', $container->getParameter('acme.section.backend.prefix'));
        $this->assertEquals('^/backend/', $container->getParameter('acme.section.backend.path'));

        $requestMatcherFrontend = $container->getDefinition('acme.section.frontend.request_matcher');
        $this->assertEquals(RequestMatcher::class, $requestMatcherFrontend->getClass());
        $this->assertEquals(
            ['%acme.section.frontend.path%', '%acme.section.frontend.host_pattern%'],
            $requestMatcherFrontend->getArguments()
        );

        $requestMatcherBackend = $container->getDefinition('acme.section.backend.request_matcher');
        $this->assertEquals(RequestMatcher::class, $requestMatcherBackend->getClass());
        $this->assertEquals(
            ['%acme.section.backend.path%', '%acme.section.backend.host_pattern%'],
            $requestMatcherBackend->getArguments()
        );

        // Ensure definitions are correct.
        $container->compile();
    }
}
