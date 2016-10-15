<?php

/*
 * This file is part of the Rollerworks AppSectioningBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\AppSectioning\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractContainerBuilderTestCase;
use Rollerworks\Bundle\AppSectioning\DependencyInjection\SectioningFactory;
use Rollerworks\Bundle\AppSectioning\SectionConfiguration;

final class SectioningFactoryTest extends AbstractContainerBuilderTestCase
{
    /**
     * @test
     */
    public function it_registers_sections_in_the_container()
    {
        $factory = new SectioningFactory($this->container, 'acme.section');

        $this->assertSame($factory, $factory->set('frontend', ['prefix' => '/', 'host' => 'example.com']));
        $this->assertSame($factory, $factory->set('backend', ['prefix' => 'backend', 'host' => 'example.com']));

        $this->assertContainerBuilderHasService(
            'park_manager.app_section.acme.section.frontend',
            SectionConfiguration::class
        );

        $this->assertContainerBuilderHasService(
            'park_manager.app_section.acme.section.backend',
            SectionConfiguration::class
        );

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'park_manager.app_section.acme.section.frontend',
            'park_manager.app_section',
            ['service_prefix' => 'acme.section', 'section_name' => 'frontend']
        );

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'park_manager.app_section.acme.section.backend',
            'park_manager.app_section',
            ['service_prefix' => 'acme.section', 'section_name' => 'backend']
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'park_manager.app_section.acme.section.frontend',
            0,
            ['prefix' => '/', 'host' => 'example.com']
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'park_manager.app_section.acme.section.backend',
            0,
            ['prefix' => 'backend', 'host' => 'example.com']
        );

        // Ensure definitions are correct.
        $this->compile();
    }

    /**
     * @test
     */
    public function it_registers_sections_by_group_in_the_container()
    {
        $factory = new SectioningFactory($this->container, 'acme.section');
        $this->assertSame($factory, $factory->set('frontend', ['prefix' => '/', 'host' => 'example.com']));
        $this->assertSame($factory, $factory->set('backend', ['prefix' => 'backend', 'host' => 'example.com']));

        $factory = new SectioningFactory($this->container, 'rollerworks.section', 'second');
        $this->assertSame($factory, $factory->set('frontend', ['prefix' => '/', 'host' => 'example.com']));
        $this->assertSame($factory, $factory->set('backend', ['prefix' => 'backend', 'host' => 'example.com']));

        $this->assertContainerBuilderHasService(
            'park_manager.app_section.acme.section.frontend',
            SectionConfiguration::class
        );

        $this->assertContainerBuilderHasService(
            'park_manager.app_section.acme.section.backend',
            SectionConfiguration::class
        );

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'park_manager.app_section.acme.section.frontend',
            'park_manager.app_section',
            ['service_prefix' => 'acme.section', 'section_name' => 'frontend']
        );

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'park_manager.app_section.acme.section.backend',
            'park_manager.app_section',
            ['service_prefix' => 'acme.section', 'section_name' => 'backend']
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'park_manager.app_section.acme.section.frontend',
            0,
            ['prefix' => '/', 'host' => 'example.com']
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'park_manager.app_section.acme.section.backend',
            0,
            ['prefix' => 'backend', 'host' => 'example.com']
        );

        // second group
        $this->assertContainerBuilderHasService(
            'park_manager.app_section.rollerworks.section.frontend',
            SectionConfiguration::class
        );

        $this->assertContainerBuilderHasService(
            'park_manager.app_section.rollerworks.section.backend',
            SectionConfiguration::class
        );

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'park_manager.app_section.rollerworks.section.frontend',
            'park_manager.app_section',
            ['service_prefix' => 'rollerworks.section', 'section_name' => 'frontend']
        );

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'park_manager.app_section.rollerworks.section.backend',
            'park_manager.app_section',
            ['service_prefix' => 'rollerworks.section', 'section_name' => 'backend']
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'park_manager.app_section.rollerworks.section.frontend',
            0,
            ['prefix' => '/', 'host' => 'example.com']
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'park_manager.app_section.rollerworks.section.backend',
            0,
            ['prefix' => 'backend', 'host' => 'example.com']
        );

        // Ensure definitions are correct.
        $this->compile();
    }
}