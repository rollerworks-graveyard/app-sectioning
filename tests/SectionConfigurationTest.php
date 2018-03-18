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
            ['', null],
            ['https:', null],
            ['https://', null],
            ['https:///', null],
            ['https://e', null],
            ['/{he}', 'Attributes in the "prefix" are not accepted.'],

            // with host requirement
            ['{he}.nl/', 'Invalid host attribute around "{he}". Expected something like: "{name;default;value1|value2}".'],
            ['{he;;}.nl/', 'Invalid host attribute around "{he;;}". Expected something like: "{name;default;value1|value2}".'],
            ['{he,;}.nl/', 'Invalid host attribute around "{he,;}". Expected something like: "{name;default;value1|value2}".'],
            ['{he;he;fo|bar|}.nl/', 'Invalid host attribute around "{he;he;fo|bar|}". Expected something like: "{name;default;value1|value2}".'],
            ['{he;he;fo|bar}.{he;w;o}/', 'Host attribute "he" is already used.'],
        ];
    }

    /**
     * @test
     * @dataProvider provideInvalidConfiguration
     */
    public function it_validates_configuration(string $config, ?string $message)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message ?? sprintf('Pattern "%s" contains one or more errors.', $config));

        new SectionConfiguration($config);
    }

    /**
     * @test
     */
    public function its_default_host_value_is_null()
    {
        $configuration = (new SectionConfiguration('/'));

        $this->assertNull($configuration->host);
        $this->assertNull($configuration->domain);
        $this->assertFalse($configuration->isSecure);
        $this->assertEquals('/', $configuration->prefix);
    }

    /**
     * @test
     */
    public function it_sets_domain_when_host_is_set_without_attributes()
    {
        $configuration = (new SectionConfiguration('example.com/'));
        $this->assertEquals('example.com', $configuration->domain);
        $this->assertEquals('example.com', $configuration->host);

        $configuration = (new SectionConfiguration('example.{tld;com;com}/'));
        $this->assertNull($configuration->domain);
        $this->assertEquals('example.{tld}', $configuration->host);
    }

    /**
     * @test
     */
    public function it_converts_the_prefix_to_lowercase_and_ensures_slashes()
    {
        $this->assertEquals('/something/', (new SectionConfiguration('Example.com/Something'))->prefix);
        $this->assertEquals('/something/', (new SectionConfiguration('Example.com/Something/'))->prefix);
        $this->assertEquals('/something/', (new SectionConfiguration('/Something/'))->prefix);
        $this->assertEquals('/foo/', (new SectionConfiguration('/foo'))->prefix);

        // Note. foo/ assumes foo is the host. Make sure the prefix always begins with a /
    }

    /**
     * @test
     */
    public function it_returns_whether_host_names_are_equal()
    {
        self::assertTrue(self::hostsEquals('example.com/', 'example.com/'));
        self::assertTrue(self::hostsEquals('/', 'example.com/'));
        self::assertTrue(self::hostsEquals('https://example.com/', 'http://example.com/'));
        self::assertTrue(self::hostsEquals('example.{head;com;com}/', 'example.{tld;com;com}/'));
        self::assertTrue(self::hostsEquals('example.{head;com;com}/', 'example.{tld;com;com|nl}/'));
        self::assertTrue(self::hostsEquals('example.{head;com;com|nl}/', 'example.{tld;com;com}/'));
        self::assertTrue(self::hostsEquals('example.nl/', 'example.{tld;com;com|nl}/'));

        self::assertFalse(self::hostsEquals('example.com/', 'example.com3/'));
        self::assertFalse(self::hostsEquals('example.he.com/', 'example.com/'));
        self::assertFalse(self::hostsEquals('example.{head;nl;nl|nu}/', 'example.{tld;com;com}/'));
        self::assertFalse(self::hostsEquals('example.{head;com;com}.com/', 'example.{tld;com;com}/'));
        self::assertFalse(self::hostsEquals('example.{tld;com;com}/', 'example.{head;com;com}.com/'));
    }

    private static function hostsEquals(string $pattern1, string $pattern2): bool
    {
        return (new SectionConfiguration($pattern1))->hostEquals(new SectionConfiguration($pattern2));
    }
}
