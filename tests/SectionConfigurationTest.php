<?php

/*
 * This file is part of the Park-Manager AppSectioningBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ParkManager\Bundle\AppSectioning\Tests;

use ParkManager\Bundle\AppSectioning\SectionConfiguration;

final class SectionConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_requires_a_prefix()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('AppSection prefix cannot be empty. Use at least "/".');

        new SectionConfiguration([]);
    }

    /**
     * @test
     */
    public function its_default_host_value_is_null()
    {
        $configuration = (new SectionConfiguration(['prefix' => '/']))->getConfig();

        $this->assertArrayHasKey('prefix', $configuration);
        $this->assertArrayHasKey('host', $configuration);

        $this->assertNull($configuration['host']);
    }

    /**
     * @test
     */
    public function it_always_provides_a_prefix_and_host_configuration()
    {
        $configuration = (new SectionConfiguration(['prefix' => 'something', 'host' => 'example.com']))->getConfig();

        $this->assertArrayHasKey('prefix', $configuration);
        $this->assertArrayHasKey('host', $configuration);
    }

    /**
     * @test
     */
    public function it_converts_the_prefix_to_lowercase_and_ensures_slashes()
    {
        $configuration = (new SectionConfiguration(['prefix' => 'Something', 'host' => 'Example.Com']))->getConfig();
        $this->assertEquals('something/', $configuration['prefix']);

        $configuration = (new SectionConfiguration(['prefix' => '/', 'host' => 'Example.Com']))->getConfig();
        $this->assertEquals('/', $configuration['prefix']);

        $configuration = (new SectionConfiguration(['prefix' => '//', 'host' => 'Example.Com']))->getConfig();
        $this->assertEquals('/', $configuration['prefix']);

        $configuration = (new SectionConfiguration(['prefix' => '/foo/', 'host' => 'Example.Com']))->getConfig();
        $this->assertEquals('foo/', $configuration['prefix']);

        $configuration = (new SectionConfiguration(['prefix' => '/Something/', 'host' => 'Example.Com']))->getConfig();
        $this->assertEquals('something/', $configuration['prefix']);
    }

    /**
     * @test
     */
    public function it_converts_the_host_to_lowercase()
    {
        $configuration = (new SectionConfiguration(['prefix' => '/', 'host' => 'Example.Com']))->getConfig();
        $this->assertEquals('example.com', $configuration['host']);
    }

    /**
     * This needs to be removed once support is added.
     *
     * @test
     */
    public function it_throws_when_placeholders_are_found_prefix()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Placeholders in the "host" and/or "prefix" are not supported yet.');

        new SectionConfiguration(['prefix' => '/{_local}/']);
    }

    /**
     * This needs to be removed once support is added.
     *
     * @test
     */
    public function it_throws_when_placeholders_are_found_host()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Placeholders in the "host" and/or "prefix" are not supported yet.');

        new SectionConfiguration(['prefix' => '/', 'host' => '{locale}.example.com']);
    }
}
