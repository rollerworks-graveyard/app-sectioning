<?php

/*
 * This file is part of the ParkManager AppSectioning package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ParkManager\Component\AppSectioning\Tests;

use ParkManager\Component\AppSectioning\AppSectionsValidator;
use ParkManager\Component\AppSectioning\Exception\ValidatorException;

final class AppSectionsValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_validates_a_single_section_with_prefix_as_positive()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', [
            'prefix' => '/',
        ]);

        $this->assertTrue($validator->validate());
    }

    /**
     * @test
     */
    public function it_validates_a_single_section_with_prefix_and_host_as_positive()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', [
            'prefix' => '/',
            'host' => '',
        ]);

        $this->assertTrue($validator->validate());
    }

    /**
     * @test
     */
    public function it_validates_a_section_with_prefix_and_custom_matching_path_as_positive()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', [
            'prefix' => '/',
            'host' => '^/',
        ]);

        $this->assertTrue($validator->validate());
    }

    /**
     * @test
     */
    public function it_validates_a_section_with_host_and_custom_matching_host_pattern_as_positive()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', [
            'prefix' => '/',
            'host' => 'example.com',
            'host_pattern' => 'example.com$',
        ]);

        $this->assertTrue($validator->validate());
    }

    /**
     * @test
     */
    public function it_validates_a_section_with_prefix_and_custom_matching_path_path_as_positive()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', [
            'prefix' => '/foobar',
            'path' => '^/foobar',
        ]);

        $this->assertTrue($validator->validate());
    }

    /**
     * @test
     */
    public function it_matches_path_as_case_insensitive()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', [
            'prefix' => '/Something',
            'path' => '^/something',
        ]);

        $this->assertTrue($validator->validate());
    }

    /**
     * @test
     */
    public function it_matches_host_as_case_insensitive()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', [
            'prefix' => '/Something',
            'path' => '^/something',
        ]);

        $this->assertTrue($validator->validate());
    }

    /**
     * @test
     */
    public function it_validates_a_sections_with_same_prefix_and_differing_hosts_as_positive()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', [
            'prefix' => '/',
            'host' => 'example.com',
        ]);
        $validator->set('second', [
            'prefix' => '/',
            'host' => 'example2.com',
        ]);

        $this->assertTrue($validator->validate());
    }

    /**
     * @test
     */
    public function it_validates_a_sections_with_same_host_and_differing_prefix_as_positive()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', [
            'prefix' => '/',
            'host' => 'example.com',
        ]);
        $validator->set('second', [
            'prefix' => '/something',
            'host' => 'example.com',
        ]);

        $this->assertTrue($validator->validate());
    }

    /**
     * @test
     */
    public function it_throws_an_ValidatorException_when_section_conflicts()
    {
        $validator = new AppSectionsValidator();
        $validator->set('first', [
            'prefix' => '/',
            'host' => 'example.com',
        ]);

        $validator->set('second', [
            'prefix' => '/', // same as 'first'
            'host' => 'example.com',
        ]);

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
        $validator->set('first', [
            'prefix' => '/',
            'host' => 'example.com',
        ]);

        $validator->set('second', [
            'prefix' => '/', // same as 'first'
            'host' => 'example.com',
        ]);

        $validator->set('third', [
            'prefix' => '/', // same as 'first'
            'host' => 'example.com',
        ]);

        //
        $validator->set('first1', [
            'prefix' => '/',
        ]);

        $validator->set('second2', [
            'prefix' => '/', // same as 'first1'
        ]);

        $validator->set('good', [
            'prefix' => '/something',
        ]);

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
        $validator->set('first', [
            'prefix' => '/',
            'host' => 'example.com',
        ]);

        $validator->set('second', [
            'prefix' => '/', // same as 'first'
            'host' => 'example.com',
        ]);

        $this->assertFalse($validator->validate(false));
    }
}
