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

use PHPUnit\Framework\TestCase;
use Rollerworks\Component\AppSectioning\Exception\ValidatorException;
use Rollerworks\Component\AppSectioning\SectionConfiguration;
use Rollerworks\Component\AppSectioning\SectionsConfigurator;
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
        $configurator->set('frontend', new SectionConfiguration('/client/'));
        $configurator->set('backend', new SectionConfiguration('https://example.com/backend'));

        $this->assertEquals(
            [
                'frontend' => [
                    'is_secure' => false,
                    'host' => null,
                    'domain' => null,
                    'host_pattern' => null,
                    'prefix' => 'client/',
                    'path' => '^/client/',
                    'requirements' => [],
                    'defaults' => [],
                ],
                'backend' => [
                    'is_secure' => true,
                    'host' => 'example.com',
                    'domain' => 'example.com',
                    'host_pattern' => '^example\.com$',
                    'prefix' => 'backend/',
                    'path' => '^/backend/',
                    'requirements' => [],
                    'defaults' => [],
                ],
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
        $configurator->set('frontend', new SectionConfiguration('example.net/'));
        $configurator->set('backend', new SectionConfiguration('example.com/'));

        $this->assertEquals(
            [
                'frontend' => [
                    'is_secure' => false,
                    'host' => 'example.net',
                    'domain' => 'example.net',
                    'host_pattern' => '^example\.net$',
                    'prefix' => '/',
                    'path' => '^/',
                    'requirements' => [],
                    'defaults' => [],
                ],
                'backend' => [
                    'is_secure' => false,
                    'host' => 'example.com',
                    'domain' => 'example.com',
                    'host_pattern' => '^example\.com$',
                    'prefix' => '/',
                    'path' => '^/',
                    'requirements' => [],
                    'defaults' => [],
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
        $configurator->set('frontend', new SectionConfiguration('/'));
        $configurator->set('backend', new SectionConfiguration('/backend'));
        $configurator->set('api', new SectionConfiguration('/api'));

        $this->assertEquals(
            [
                'frontend' => [
                    'is_secure' => false,
                    'host' => null,
                    'domain' => null,
                    'host_pattern' => null,
                    'prefix' => '/',
                    'path' => '^/(?!(backend|api)/)',
                    'requirements' => [],
                    'defaults' => [],
                ],
                'backend' => [
                    'is_secure' => false,
                    'host' => null,
                    'domain' => null,
                    'host_pattern' => null,
                    'prefix' => 'backend/',
                    'path' => '^/backend/',
                    'requirements' => [],
                    'defaults' => [],
                ],
                'api' => [
                    'is_secure' => false,
                    'host' => null,
                    'domain' => null,
                    'host_pattern' => null,
                    'prefix' => 'api/',
                    'path' => '^/api/',
                    'requirements' => [],
                    'defaults' => [],
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
        $configurator->set('frontend', new SectionConfiguration('/'));
        $configurator->set('backend', new SectionConfiguration('/backend'));
        $configurator->set('backend_api', new SectionConfiguration('/api/backend'));
        $configurator->set('api', new SectionConfiguration('/api'));

        $this->assertEquals(
            [
                'frontend' => [
                    'is_secure' => false,
                    'host' => null,
                    'domain' => null,
                    'host_pattern' => null,
                    'prefix' => '/',
                    'path' => '^/(?!(backend|api)/)',
                    'requirements' => [],
                    'defaults' => [],
                ],
                'backend' => [
                    'is_secure' => false,
                    'host' => null,
                    'domain' => null,
                    'host_pattern' => null,
                    'prefix' => 'backend/',
                    'path' => '^/backend/',
                    'requirements' => [],
                    'defaults' => [],
                ],
                'backend_api' => [
                    'is_secure' => false,
                    'host' => null,
                    'domain' => null,
                    'host_pattern' => null,
                    'prefix' => 'api/backend/',
                    'path' => '^/api/backend/',
                    'requirements' => [],
                    'defaults' => [],
                ],
                'api' => [
                    'is_secure' => false,
                    'host' => null,
                    'domain' => null,
                    'host_pattern' => null,
                    'prefix' => 'api/',
                    'path' => '^/api/(?!(backend)/)',
                    'requirements' => [],
                    'defaults' => [],
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
        $configurator->set('frontend', new SectionConfiguration('example.com/'));
        $configurator->set('backend', new SectionConfiguration('example.com/backend'));
        $configurator->set('backend_api', new SectionConfiguration('example.com/api/backend'));
        $configurator->set('api', new SectionConfiguration('example.com/api'));

        $this->assertEquals(
            [
                'frontend' => [
                    'is_secure' => false,
                    'domain' => 'example.com',
                    'host' => 'example.com',
                    'host_pattern' => '^example\.com$',
                    'prefix' => '/',
                    'path' => '^/(?!(backend|api)/)',
                    'requirements' => [],
                    'defaults' => [],
                ],
                'backend' => [
                    'is_secure' => false,
                    'domain' => 'example.com',
                    'host' => 'example.com',
                    'host_pattern' => '^example\.com$',
                    'prefix' => 'backend/',
                    'path' => '^/backend/',
                    'requirements' => [],
                    'defaults' => [],
                ],
                'backend_api' => [
                    'is_secure' => false,
                    'domain' => 'example.com',
                    'host' => 'example.com',
                    'host_pattern' => '^example\.com$',
                    'prefix' => 'api/backend/',
                    'path' => '^/api/backend/',
                    'requirements' => [],
                    'defaults' => [],
                ],
                'api' => [
                    'is_secure' => false,
                    'domain' => 'example.com',
                    'host' => 'example.com',
                    'host_pattern' => '^example\.com$',
                    'prefix' => 'api/',
                    'path' => '^/api/(?!(backend)/)',
                    'requirements' => [],
                    'defaults' => [],
                ],
            ],
            $configurator->exportConfiguration()
        );
    }

    /**
     * @test
     */
    public function its_configured_path_excludes_other_sub_paths_in_host_pattern()
    {
        $configurator = new SectionsConfigurator();
        $configurator->set('frontend', new SectionConfiguration('example.{tld;com;com|net}/'));
        $configurator->set('backend', new SectionConfiguration('example.com/backend'));
        $configurator->set('backend_api', new SectionConfiguration('example.com/api/backend'));
        $configurator->set('api', new SectionConfiguration('example.com/api'));

        $this->assertEquals(
            [
                'frontend' => [
                    'is_secure' => false,
                    'domain' => null,
                    'host' => 'example.{tld}',
                    'host_pattern' => '^example\.(?P<tld>com|net)$',
                    'prefix' => '/',
                    'path' => '^/(?!(backend|api)/)',
                    'requirements' => ['tld' => 'com|net'],
                    'defaults' => ['tld' => 'com'],
                ],
                'backend' => [
                    'is_secure' => false,
                    'domain' => 'example.com',
                    'host' => 'example.com',
                    'host_pattern' => '^example\.com$',
                    'prefix' => 'backend/',
                    'path' => '^/backend/',
                    'requirements' => [],
                    'defaults' => [],
                ],
                'backend_api' => [
                    'is_secure' => false,
                    'domain' => 'example.com',
                    'host' => 'example.com',
                    'host_pattern' => '^example\.com$',
                    'prefix' => 'api/backend/',
                    'path' => '^/api/backend/',
                    'requirements' => [],
                    'defaults' => [],
                ],
                'api' => [
                    'is_secure' => false,
                    'domain' => 'example.com',
                    'host' => 'example.com',
                    'host_pattern' => '^example\.com$',
                    'prefix' => 'api/',
                    'path' => '^/api/(?!(backend)/)',
                    'requirements' => [],
                    'defaults' => [],
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
        $configurator->set('frontend', new SectionConfiguration('https://example.com/'));
        $configurator->set('backend', new SectionConfiguration('example.{tld;com;com|net}/backend')
        );

        $configurator->registerToContainer($container, 'acme.section');

        $this->assertTrue($container->hasParameter('acme.section.frontend.is_secure'));
        $this->assertTrue($container->hasParameter('acme.section.frontend.channel'));
        $this->assertTrue($container->hasParameter('acme.section.frontend.domain'));
        $this->assertTrue($container->hasParameter('acme.section.frontend.host'));
        $this->assertTrue($container->hasParameter('acme.section.frontend.host_pattern'));
        $this->assertTrue($container->hasParameter('acme.section.frontend.requirements'));
        $this->assertTrue($container->hasParameter('acme.section.frontend.defaults'));
        $this->assertTrue($container->hasParameter('acme.section.frontend.prefix'));
        $this->assertTrue($container->hasParameter('acme.section.frontend.path'));
        $this->assertTrue($container->hasDefinition('acme.section.frontend.request_matcher'));

        $this->assertTrue($container->hasParameter('acme.section.backend.is_secure'));
        $this->assertTrue($container->hasParameter('acme.section.backend.channel'));
        $this->assertTrue($container->hasParameter('acme.section.backend.domain'));
        $this->assertTrue($container->hasParameter('acme.section.backend.host'));
        $this->assertTrue($container->hasParameter('acme.section.backend.host_pattern'));
        $this->assertTrue($container->hasParameter('acme.section.backend.requirements'));
        $this->assertTrue($container->hasParameter('acme.section.backend.defaults'));
        $this->assertTrue($container->hasParameter('acme.section.backend.prefix'));
        $this->assertTrue($container->hasParameter('acme.section.backend.path'));
        $this->assertTrue($container->hasDefinition('acme.section.backend.request_matcher'));

        $this->assertTrue($container->getParameter('acme.section.frontend.is_secure'));
        $this->assertEquals('https', $container->getParameter('acme.section.frontend.channel'));
        $this->assertEquals('example.com', $container->getParameter('acme.section.frontend.domain'));
        $this->assertEquals('example.com', $container->getParameter('acme.section.frontend.host'));
        $this->assertEquals('^example\.com$', $container->getParameter('acme.section.frontend.host_pattern'));
        $this->assertEquals([], $container->getParameter('acme.section.frontend.defaults'));
        $this->assertEquals([], $container->getParameter('acme.section.frontend.requirements'));
        $this->assertEquals('/', $container->getParameter('acme.section.frontend.prefix'));
        $this->assertEquals('^/(?!(backend)/)', $container->getParameter('acme.section.frontend.path'));

        $this->assertFalse($container->getParameter('acme.section.backend.is_secure'));
        $this->assertNull($container->getParameter('acme.section.backend.channel'));
        $this->assertNull($container->getParameter('acme.section.backend.domain'));
        $this->assertEquals('example.{tld}', $container->getParameter('acme.section.backend.host'));
        $this->assertEquals('^example\.(?P<tld>com|net)$', $container->getParameter('acme.section.backend.host_pattern'));
        $this->assertEquals(['tld' => 'com'], $container->getParameter('acme.section.backend.defaults'));
        $this->assertEquals(['tld' => 'com|net'], $container->getParameter('acme.section.backend.requirements'));
        $this->assertEquals('backend/', $container->getParameter('acme.section.backend.prefix'));
        $this->assertEquals('^/backend/', $container->getParameter('acme.section.backend.path'));

        $requestMatcherFrontend = $container->getDefinition('acme.section.frontend.request_matcher');
        $this->assertEquals(RequestMatcher::class, $requestMatcherFrontend->getClass());
        $this->assertEquals(['^/(?!(backend)/)', '^example\.com$'], $requestMatcherFrontend->getArguments());

        $requestMatcherBackend = $container->getDefinition('acme.section.backend.request_matcher');
        $this->assertEquals(RequestMatcher::class, $requestMatcherBackend->getClass());
        $this->assertEquals(['^/backend/', '^example\.(?P<tld>com|net)$'], $requestMatcherBackend->getArguments());

        // Ensure definitions are correct.
        $container->compile();
    }

    /**
     * @test
     */
    public function it_throws_an_ValidatorException_when_section_conflicts()
    {
        $configurator = new SectionsConfigurator();
        $configurator->set('first', new SectionConfiguration('example.com/'));
        $configurator->set('second', new SectionConfiguration('example.com/'));

        $failedSections = [
            // primary => [host, prefix, conflicts]
            'first' => ['^example\.com$', '/', ['second']],
        ];

        $expectedMessage = ValidatorException::sectionsConfigConflict($failedSections)->getMessage();
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage($expectedMessage);

        $configurator->process();
    }

    /**
     * @test
     */
    public function it_throws_an_ValidatorException_when_section_conflicts_with_patterns()
    {
        $configurator = new SectionsConfigurator();
        $configurator->set('first', new SectionConfiguration('example.{tld;com;com|net}/'));
        $configurator->set('second', new SectionConfiguration('example.{ext;net;com|net}/'));
        $configurator->set('third', new SectionConfiguration('example.com/app'));

        $failedSections = [
            // primary => [host, prefix, conflicts]
            'first' => ['^example\.(?P<tld>com|net)$', '/', ['second']],
        ];

        $expectedMessage = ValidatorException::sectionsConfigConflict($failedSections)->getMessage();
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage($expectedMessage);

        $configurator->process();
    }

    /**
     * Same as it_throws_an_ValidatorException_when_section_conflicts but tests with more
     * sections to ensure all are validated after a failure.
     *
     * @test
     */
    public function it_throws_an_ValidatorException_when_sections_conflicts()
    {
        $configurator = new SectionsConfigurator();
        $configurator->set('first', new SectionConfiguration('example.com/'));
        $configurator->set('second', new SectionConfiguration('example.com/'));
        $configurator->set('third', new SectionConfiguration('example.com/'));

        $configurator->set('first1', new SectionConfiguration('/foo'));
        $configurator->set('second2', new SectionConfiguration('/foo'));

        $configurator->set('good', new SectionConfiguration('/something'));

        $failedSections = [
            // primary => [host, prefix, conflicts]
            'first' => ['^example\.com$', '/', ['second', 'third']],
            'first1' => ['', 'foo/', ['second2']],
        ];

        $expectedMessage = ValidatorException::sectionsConfigConflict($failedSections)->getMessage();
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage($expectedMessage);

        $configurator->process();
    }

    /**
     * Same as it_throws_an_ValidatorException_when_sections_conflicts but tests
     * without host to ensure that '/' will conflict with hostA.com/.
     *
     * @test
     */
    public function it_throws_an_ValidatorException_when_sections_conflicts_by_prefix_and_no_host()
    {
        $configurator = new SectionsConfigurator();
        $configurator->set('first', new SectionConfiguration('example.com/'));
        $configurator->set('second', new SectionConfiguration('example.com/'));
        $configurator->set('third', new SectionConfiguration('example.com/'));

        //
        // conflicts with 'first', no host (so '*') and equal prefix '/'
        $configurator->set('first1', new SectionConfiguration('/')); // conflicts with 'first' because of no host and equal '/'
        $configurator->set('second2', new SectionConfiguration('/')); // conflicts with 'first' because of no host and equal '/'
        $configurator->set('good', new SectionConfiguration('/something'));

        $failedSections = [
            // primary => [host, prefix, conflicts]
            'first' => ['^example\.com$', '/', ['second', 'third', 'first1', 'second2']],
        ];

        $expectedMessage = ValidatorException::sectionsConfigConflict($failedSections)->getMessage();
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage($expectedMessage);

        $configurator->process();
    }
}
