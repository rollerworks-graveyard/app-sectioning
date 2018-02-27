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
use Rollerworks\Component\AppSectioning\SectionConfiguration;

final class SectionConfigurationTest extends TestCase
{
    public function provideInvalidConfiguration()
    {
        return [
            [[], 'Prefix must be an string and cannot be empty. Use at least "/".'],
            [['prefix' => []], 'Prefix must be an string and cannot be empty. Use at least "/".'],
            [['prefix' => '{he}/'], 'Placeholders in the "prefix" are not accepted.'],
            [['prefix' => '/', 'defaults' => ''], 'Keys "requirements" and "default" must be arrays or absent.'],
            [['prefix' => '/', 'requirements' => ''], 'Keys "requirements" and "default" must be arrays or absent.'],

            // with host requirement
            [['prefix' => '/', 'host' => '{he}.nl'], 'Missing requirement for attribute "he".'],
            [
                ['prefix' => '/', 'host' => '{he}.nl', 'requirements' => ['he' => 'ok']],
                'Missing default value for attribute "he".',
            ],
            [
                ['prefix' => '/', 'host' => '{he}.nl', 'requirements' => ['he' => 'ok?'], 'defaults' => ['he' => 'ok']],
                'A host requirement can only hold letters, numbers with middle and underscores separated by "|" (to allow more combinations).',
            ],
            [
                ['prefix' => '/', 'host' => '{he}.nl', 'requirements' => ['he' => '|'], 'defaults' => ['he' => 'ok']],
                'A host requirement can only hold letters, numbers with middle and underscores separated by "|" (to allow more combinations).',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideInvalidConfiguration
     */
    public function it_validates_configuration(array $config, $message)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new SectionConfiguration($config);
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
     * This needs to be removed once support is added.
     *
     * @test
     */
    public function it_throws_when_placeholders_are_found_prefix()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Placeholders in the "prefix" are not accepted.');

        new SectionConfiguration(['prefix' => '/{_local}/']);
    }

    /**
     * @test
     */
    public function it_returns_whether_host_names_are_equal()
    {
        self::assertTrue(self::hostsEquals('example.com', 'example.com'));
        self::assertTrue(self::hostsEqualsWithPattern('example.{head}', 'example.{tld}', ['head' => 'com'], ['tld' => 'com']));
        self::assertTrue(self::hostsEqualsWithPattern('example.{head}', 'example.{tld}', ['head' => 'com|nl'], ['tld' => 'com']));
        self::assertTrue(self::hostsEqualsWithPattern('example.{head}', 'example.{tld}', ['head' => 'com'], ['tld' => 'com|nl']));
        self::assertTrue(self::hostsEqualsWithPattern('example.nl', 'example.{tld}', [], ['tld' => 'com|nl']));

        self::assertFalse(self::hostsEquals('example.com', 'example.com3'));
        self::assertFalse(self::hostsEquals('example.he.com', 'example.com'));
        self::assertFalse(self::hostsEqualsWithPattern('example.{head}', 'example.{tld}', ['head' => 'nl|nu'], ['tld' => 'com']));
        self::assertFalse(self::hostsEqualsWithPattern('example.{head}.com', 'example.{tld}', ['head' => 'com'], ['tld' => 'com']));
        self::assertFalse(self::hostsEqualsWithPattern('example.{tld}', 'example.{head}.com', ['tld' => 'com'], ['head' => 'com']));
    }

    private static function hostsEquals(string $host1, string $host2): bool
    {
        return (new SectionConfiguration(['prefix' => '/', 'host' => $host1]))->hostEquals(
            new SectionConfiguration(['prefix' => '/', 'host' => $host2])
        );
    }

    private static function hostsEqualsWithPattern(string $host1, string $host2, array $requirements1, array $requirements2): bool
    {
        return (new SectionConfiguration(['prefix' => '/', 'host' => $host1, 'requirements' => $requirements1, 'defaults' => ['tld' => 'com', 'head' => 'com']]))->hostEquals(
            new SectionConfiguration(['prefix' => '/', 'host' => $host2, 'requirements' => $requirements2, 'defaults' => ['tld' => 'com', 'head' => 'com']])
        );
    }
}
