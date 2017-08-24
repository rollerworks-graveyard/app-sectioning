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

namespace Rollerworks\Bundle\AppSectioningBundle\Tests;

use PHPUnit\Framework\TestCase;
use Rollerworks\Bundle\AppSectioningBundle\AppSectionsValidator;
use Rollerworks\Bundle\AppSectioningBundle\Exception\ValidatorException;
use Rollerworks\Bundle\AppSectioningBundle\SectionConfiguration;

final class AppSectionsValidatorTest extends TestCase
{
    /** @var AppSectionsValidator */
    private $validator;

    /** @before */
    public function setUpValidator()
    {
        $this->validator = new AppSectionsValidator();
    }

    /**
     * @test
     */
    public function it_validates_a_single_section_with_prefix_as_positive()
    {
        $this->validator->set('first', new SectionConfiguration([
            'prefix' => '/',
        ]));

        $this->assertTrue($this->validator->validate());
    }

    /**
     * @test
     */
    public function it_validates_a_single_section_with_prefix_and_host_as_positive()
    {
        $this->validator->set('first', new SectionConfiguration([
            'prefix' => '/',
            'host' => '',
        ]));

        $this->assertTrue($this->validator->validate());
    }

    /**
     * @test
     */
    public function it_validates_a_section_with_prefix_and_custom_matching_path_as_positive()
    {
        $this->validator->set('first', new SectionConfiguration([
            'prefix' => '/',
            'host' => '^/',
        ]));

        $this->assertTrue($this->validator->validate());
    }

    /**
     * @test
     */
    public function it_validates_a_section_with_host_and_custom_matching_host_pattern_as_positive()
    {
        $this->validator->set('first', new SectionConfiguration([
            'prefix' => '/',
            'host' => 'example.com',
            'host_pattern' => 'example.com$',
        ]));

        $this->assertTrue($this->validator->validate());
    }

    /**
     * @test
     */
    public function it_validates_a_section_with_prefix_and_custom_matching_path_path_as_positive()
    {
        $this->validator->set('first', new SectionConfiguration([
            'prefix' => '/foobar',
            'path' => '^/foobar',
        ]));

        $this->assertTrue($this->validator->validate());
    }

    /**
     * @test
     */
    public function it_matches_path_as_case_insensitive()
    {
        $this->validator->set('first', new SectionConfiguration([
            'prefix' => '/Something',
            'path' => '^/something',
        ]));

        $this->assertTrue($this->validator->validate());
    }

    /**
     * @test
     */
    public function it_matches_host_as_case_insensitive()
    {
        $this->validator->set('first', new SectionConfiguration([
            'prefix' => '/Something',
            'path' => '^/something',
        ]));

        $this->assertTrue($this->validator->validate());
    }

    /**
     * @test
     */
    public function it_validates_a_sections_with_same_prefix_and_differing_hosts_as_positive()
    {
        $this->validator->set('first', new SectionConfiguration([
            'prefix' => '/',
            'host' => 'example.com',
        ]));
        $this->validator->set('second', new SectionConfiguration([
            'prefix' => '/',
            'host' => 'example2.com',
        ]));

        $this->assertTrue($this->validator->validate());
    }

    /**
     * @test
     */
    public function it_validates_a_sections_with_same_host_and_differing_prefix_as_positive()
    {
        $this->validator->set('first', new SectionConfiguration([
            'prefix' => '/',
            'host' => 'example.com',
        ]));
        $this->validator->set('second', new SectionConfiguration([
            'prefix' => '/something',
            'host' => 'example.com',
        ]));

        $this->assertTrue($this->validator->validate());
    }

    /**
     * @test
     */
    public function it_throws_an_ValidatorException_when_section_conflicts()
    {
        $this->validator->set('first', new SectionConfiguration([
            'prefix' => '/',
            'host' => 'example.com',
        ]));

        $this->validator->set('second', new SectionConfiguration([
            'prefix' => '/', // same as 'first'
            'host' => 'example.com',
        ]));

        $failedSections = [
            // primary => [host, prefix, conflicts]
            'first' => ['^example\.com$', '/', ['second']],
        ];

        $expectedMessage = ValidatorException::sectionsConfigConflict($failedSections)
            ->getMessage();
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->validator->validate();
    }

    /**
     * @test
     */
    public function it_throws_an_ValidatorException_when_section_conflicts_with_patterns()
    {
        $this->validator->set('first', new SectionConfiguration([
            'prefix' => '/',
            'host' => 'example.{tld}',
            'requirements' => ['tld' => 'com|net'],
            'defaults' => ['tld' => 'com'],
        ]));

        $this->validator->set('second', new SectionConfiguration([
            'prefix' => '/', // same as 'first'
            'host' => 'example.{ext}',
            'requirements' => ['tld' => 'com|net'],
            'defaults' => ['tld' => 'net'],
        ]));

        $this->validator->set('third', new SectionConfiguration([
            'prefix' => '/app',
            'host' => 'example.com',
        ]));

        $failedSections = [
            // primary => [host, prefix, conflicts]
            'first' => ['^example\.(?P<tld>com|net)$', '/', ['second']],
        ];

        $expectedMessage = ValidatorException::sectionsConfigConflict($failedSections)
            ->getMessage();

        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->validator->validate();
    }

    /**
     * Same as it_throws_an_ValidatorException_when_section_conflicts but tests with more
     * sections to ensure all are validated after a failure.
     *
     * @test
     */
    public function it_throws_an_ValidatorException_when_sections_conflicts()
    {
        $this->validator->set('first', new SectionConfiguration([
            'prefix' => '/',
            'host' => 'example.com',
        ]));

        $this->validator->set('second', new SectionConfiguration([
            'prefix' => '/', // same as 'first'
            'host' => 'example.com',
        ]));

        $this->validator->set('third', new SectionConfiguration([
            'prefix' => '/', // same as 'first'
            'host' => 'example.com',
        ]));

        $this->validator->set('first1', new SectionConfiguration([
            'prefix' => '/foo',
        ]));

        $this->validator->set('second2', new SectionConfiguration([
            'prefix' => '/foo', // same as 'first1'
        ]));

        $this->validator->set('good', new SectionConfiguration([
            'prefix' => '/something',
        ]));

        $failedSections = [
            // primary => [host, prefix, conflicts]
            'first' => ['^example\.com$', '/', ['second', 'third']],
            'first1' => ['', 'foo/', ['second2']],
        ];

        $expectedMessage = ValidatorException::sectionsConfigConflict($failedSections)
            ->getMessage();

        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->validator->validate();
    }

    /**
     * Same as it_throws_an_ValidatorException_when_sections_conflicts but tests
     * without host to ensure that '/' will conflict with hostA.com/.
     *
     * @test
     */
    public function it_throws_an_ValidatorException_when_sections_conflicts_by_prefix_and_no_host()
    {
        $this->validator->set('first', new SectionConfiguration([
            'prefix' => '/',
            'host' => 'example.com',
        ]));

        $this->validator->set('second', new SectionConfiguration([
            'prefix' => '/', // same as 'first'
            'host' => 'example.com',
        ]));

        $this->validator->set('third', new SectionConfiguration([
            'prefix' => '/', // same as 'first'
            'host' => 'example.com',
        ]));

        //
        // conflicts with 'first', no host (so '*') and equal prefix '/'
        $this->validator->set('first1', new SectionConfiguration([
            'prefix' => '/', // conflicts with 'first' because of no host and equal '/'
        ]));

        $this->validator->set('second2', new SectionConfiguration([
            'prefix' => '/', // conflicts with 'first' because of no host and equal '/'
        ]));

        $this->validator->set('good', new SectionConfiguration([
            'prefix' => '/something',
        ]));

        $failedSections = [
            // primary => [host, prefix, conflicts]
            'first' => ['^example\.com$', '/', ['second', 'third', 'first1', 'second2']],
        ];

        $expectedMessage = ValidatorException::sectionsConfigConflict($failedSections)
            ->getMessage();

        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->validator->validate();
    }
}
