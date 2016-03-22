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

use ParkManager\Bundle\AppSectioning\AppSectionsValidator;
use ParkManager\Bundle\AppSectioning\Exception\ValidatorException;
use ParkManager\Bundle\AppSectioning\SectionConfiguration;

final class AppSectionsValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_validates_a_single_section_with_prefix_as_positive()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', new SectionConfiguration([
            'prefix' => '/',
        ]));

        $this->assertTrue($validator->validate());
    }

    /**
     * @test
     */
    public function it_validates_a_single_section_with_prefix_and_host_as_positive()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', new SectionConfiguration([
            'prefix' => '/',
            'host' => '',
        ]));

        $this->assertTrue($validator->validate());
    }

    /**
     * @test
     */
    public function it_validates_a_section_with_prefix_and_custom_matching_path_as_positive()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', new SectionConfiguration([
            'prefix' => '/',
            'host' => '^/',
        ]));

        $this->assertTrue($validator->validate());
    }

    /**
     * @test
     */
    public function it_validates_a_section_with_host_and_custom_matching_host_pattern_as_positive()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', new SectionConfiguration([
            'prefix' => '/',
            'host' => 'example.com',
            'host_pattern' => 'example.com$',
        ]));

        $this->assertTrue($validator->validate());
    }

    /**
     * @test
     */
    public function it_validates_a_section_with_prefix_and_custom_matching_path_path_as_positive()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', new SectionConfiguration([
            'prefix' => '/foobar',
            'path' => '^/foobar',
        ]));

        $this->assertTrue($validator->validate());
    }

    /**
     * @test
     */
    public function it_matches_path_as_case_insensitive()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', new SectionConfiguration([
            'prefix' => '/Something',
            'path' => '^/something',
        ]));

        $this->assertTrue($validator->validate());
    }

    /**
     * @test
     */
    public function it_matches_host_as_case_insensitive()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', new SectionConfiguration([
            'prefix' => '/Something',
            'path' => '^/something',
        ]));

        $this->assertTrue($validator->validate());
    }

    /**
     * @test
     */
    public function it_validates_a_sections_with_same_prefix_and_differing_hosts_as_positive()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', new SectionConfiguration([
            'prefix' => '/',
            'host' => 'example.com',
        ]));
        $validator->set('second', new SectionConfiguration([
            'prefix' => '/',
            'host' => 'example2.com',
        ]));

        $this->assertTrue($validator->validate());
    }

    /**
     * @test
     */
    public function it_validates_a_sections_with_same_host_and_differing_prefix_as_positive()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', new SectionConfiguration([
            'prefix' => '/',
            'host' => 'example.com',
        ]));
        $validator->set('second', new SectionConfiguration([
            'prefix' => '/something',
            'host' => 'example.com',
        ]));

        $this->assertTrue($validator->validate());
    }

    /**
     * @test
     */
    public function it_throws_an_ValidatorException_when_section_conflicts()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', new SectionConfiguration([
            'prefix' => '/',
            'host' => 'example.com',
        ]));

        $validator->set('second', new SectionConfiguration([
            'prefix' => '/', // same as 'first'
            'host' => 'example.com',
        ]));

        $failedSections = [
            // primary => [host, prefix, conflicts]
            'first' => ['example.com', '/', ['second']],
        ];

        $expectedMessage = ValidatorException::sectionsConfigConflict($failedSections)
            ->getMessage();
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage($expectedMessage);

        $validator->validate();
    }

    /**
     * Same as it_throws_an_ValidatorException_when_section_conflicts but tests with more
     * sections to ensure all are validated after a failure.
     *
     * @test
     */
    public function it_throws_an_ValidatorException_when_sections_conflicts()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', new SectionConfiguration([
            'prefix' => '/',
            'host' => 'example.com',
        ]));

        $validator->set('second', new SectionConfiguration([
            'prefix' => '/', // same as 'first'
            'host' => 'example.com',
        ]));

        $validator->set('third', new SectionConfiguration([
            'prefix' => '/', // same as 'first'
            'host' => 'example.com',
        ]));

        //
        $validator->set('first1', new SectionConfiguration([
            'prefix' => '/',
        ]));

        $validator->set('second2', new SectionConfiguration([
            'prefix' => '/', // same as 'first1'
        ]));

        $validator->set('good', new SectionConfiguration([
            'prefix' => '/something',
        ]));

        $failedSections = [
            // primary => [host, prefix, conflicts]
            'first' => ['example.com', '/', ['second', 'third']],
            'first1' => ['', '/', ['second2']],
        ];

        $expectedMessage = ValidatorException::sectionsConfigConflict($failedSections)
            ->getMessage();

        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage($expectedMessage);

        $validator->validate();
    }

    /**
     * @test
     */
    public function it_allows_to_use_boolean_instead_exception_for_failure()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', new SectionConfiguration([
            'prefix' => '/',
            'host' => 'example.com',
        ]));

        $validator->set('second', new SectionConfiguration([
            'prefix' => '/', // same as 'first'
            'host' => 'example.com',
        ]));

        $this->assertFalse($validator->validate(false));
    }
}
