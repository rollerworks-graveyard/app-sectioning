<?php

/*
 * This file is part of the Rollerworks AppSectioningBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\AppSectioning\Tests\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Rollerworks\Bundle\AppSectioning\DependencyInjection\Compiler\AppSectionsPass;
use Rollerworks\Bundle\AppSectioning\DependencyInjection\SectioningFactory;
use Rollerworks\Bundle\AppSectioning\Exception\ValidatorException;
use Rollerworks\Bundle\AppSectioning\Routing\AppSectionRouteLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\RequestMatcher;

final class AppSectionsPassTest extends AbstractCompilerPassTestCase
{
    /**
     * @test
     */
    public function it_processes_sections()
    {
        $factory = new SectioningFactory($this->container, 'acme.section');
        $factory->set('frontend', ['prefix' => '/', 'host' => 'example.com']);
        $factory->set('backend', ['prefix' => 'backend', 'host' => 'example.com']);

        $this->compile();

        $this->assertTrue($this->container->hasParameter('acme.section.frontend.host'));
        $this->assertTrue($this->container->hasParameter('acme.section.frontend.host_pattern'));
        $this->assertTrue($this->container->hasParameter('acme.section.frontend.prefix'));
        $this->assertTrue($this->container->hasParameter('acme.section.frontend.path'));
        $this->assertTrue($this->container->hasDefinition('acme.section.frontend.request_matcher'));

        $this->assertTrue($this->container->hasParameter('acme.section.backend.host'));
        $this->assertTrue($this->container->hasParameter('acme.section.backend.host_pattern'));
        $this->assertTrue($this->container->hasParameter('acme.section.backend.prefix'));
        $this->assertTrue($this->container->hasParameter('acme.section.backend.path'));
        $this->assertTrue($this->container->hasDefinition('acme.section.backend.request_matcher'));

        $this->assertEquals('example.com', $this->container->getParameter('acme.section.frontend.host'));
        $this->assertEquals('^example\.com$', $this->container->getParameter('acme.section.frontend.host_pattern'));
        $this->assertEquals('/', $this->container->getParameter('acme.section.frontend.prefix'));
        $this->assertEquals('^/(?!(backend)/)', $this->container->getParameter('acme.section.frontend.path'));

        $this->assertEquals('example.com', $this->container->getParameter('acme.section.backend.host'));
        $this->assertEquals('^example\.com$', $this->container->getParameter('acme.section.backend.host_pattern'));
        $this->assertEquals('backend/', $this->container->getParameter('acme.section.backend.prefix'));
        $this->assertEquals('^/backend/', $this->container->getParameter('acme.section.backend.path'));

        $requestMatcherFrontend = $this->container->getDefinition('acme.section.frontend.request_matcher');
        $this->assertEquals(RequestMatcher::class, $requestMatcherFrontend->getClass());
        $this->assertEquals(['^/(?!(backend)/)', '^example\.com$'], $requestMatcherFrontend->getArguments());

        $requestMatcherBackend = $this->container->getDefinition('acme.section.backend.request_matcher');
        $this->assertEquals(RequestMatcher::class, $requestMatcherBackend->getClass());
        $this->assertEquals(['^/backend/', '^example\.com$'], $requestMatcherBackend->getArguments());

        // Note. This assertion is only done once as the values are provided external
        // This only ensures the service receives the correct values
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('park_manager.app_section.route_loader', 1, [
            'frontend' => [
                'prefix' => '/',
                'host' => 'example.com',
                'host_pattern' => '^example\.com$',
                'requirements' => [
                    'host' => '^example\.com$',
                ],
                'service_prefix' => 'acme.section',
                'path' => '^/(?!(backend)/)',
            ],
            'backend' => [
                'prefix' => 'backend/',
                'host' => 'example.com',
                'host_pattern' => '^example\.com$',
                'requirements' => [
                    'host' => '^example\.com$',
                ],
                'service_prefix' => 'acme.section',
                'path' => '^/backend/',
            ],
        ]);
    }

    /**
     * @test
     */
    public function it_processes_sections_provided_by_factories()
    {
        $factory = new SectioningFactory($this->container, 'acme.section');
        $factory->set('frontend', ['prefix' => '/', 'host' => 'example.com']);

        $factory = new SectioningFactory($this->container, 'park.section');
        $factory->set('backend', ['prefix' => 'backend', 'host' => 'example.com']);

        $this->compile();

        $this->assertTrue($this->container->hasParameter('acme.section.frontend.host'));
        $this->assertTrue($this->container->hasParameter('acme.section.frontend.host_pattern'));
        $this->assertTrue($this->container->hasParameter('acme.section.frontend.prefix'));
        $this->assertTrue($this->container->hasParameter('acme.section.frontend.path'));
        $this->assertTrue($this->container->hasDefinition('acme.section.frontend.request_matcher'));

        $this->assertTrue($this->container->hasParameter('park.section.backend.host'));
        $this->assertTrue($this->container->hasParameter('park.section.backend.host_pattern'));
        $this->assertTrue($this->container->hasParameter('park.section.backend.prefix'));
        $this->assertTrue($this->container->hasParameter('park.section.backend.path'));
        $this->assertTrue($this->container->hasDefinition('park.section.backend.request_matcher'));

        $this->assertEquals('example.com', $this->container->getParameter('acme.section.frontend.host'));
        $this->assertEquals('^example\.com$', $this->container->getParameter('acme.section.frontend.host_pattern'));
        $this->assertEquals('/', $this->container->getParameter('acme.section.frontend.prefix'));
        $this->assertEquals('^/(?!(backend)/)', $this->container->getParameter('acme.section.frontend.path'));

        $this->assertEquals('example.com', $this->container->getParameter('park.section.backend.host'));
        $this->assertEquals('^example\.com$', $this->container->getParameter('park.section.backend.host_pattern'));
        $this->assertEquals('backend/', $this->container->getParameter('park.section.backend.prefix'));
        $this->assertEquals('^/backend/', $this->container->getParameter('park.section.backend.path'));

        $requestMatcherFrontend = $this->container->getDefinition('acme.section.frontend.request_matcher');
        $this->assertEquals(RequestMatcher::class, $requestMatcherFrontend->getClass());
        $this->assertEquals(['^/(?!(backend)/)', '^example\.com$'], $requestMatcherFrontend->getArguments());

        $requestMatcherBackend = $this->container->getDefinition('park.section.backend.request_matcher');
        $this->assertEquals(RequestMatcher::class, $requestMatcherBackend->getClass());
        $this->assertEquals(['^/backend/', '^example\.com$'], $requestMatcherBackend->getArguments());
    }

    /**
     * @test
     */
    public function it_validates_all_sections_for_conflicts()
    {
        $factory = new SectioningFactory($this->container, 'acme.section');
        $factory->set('frontend', ['prefix' => '/', 'host' => 'example.com']);

        $factory = new SectioningFactory($this->container, 'park.section');
        $factory->set('backend', ['prefix' => '/', 'host' => 'example.com']);

        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            'AppSection(s) "backend" conflict with "frontend", all have the same host "example.com" '.
            'and prefix "/" configured.'
        );

        $this->compile();
    }

    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->register('routing.loader', get_class($this->getMock(LoaderInterface::class)));
        $container->register('park_manager.app_section.route_loader', AppSectionRouteLoader::class)
            ->setArguments([new Reference('routing.loader'), []]);

        $container->addCompilerPass(new AppSectionsPass());
    }
}
