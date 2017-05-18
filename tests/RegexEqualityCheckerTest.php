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
use Rollerworks\Bundle\AppSectioningBundle\RegexEqualityChecker;

final class RegexEqualityCheckerTest extends TestCase
{
    /**
     * @test
     */
    public function it_equals_for_same_regex()
    {
        self::assertTrue(RegexEqualityChecker::equals('foobar', 'foobar'));
    }

    /**
     * @test
     */
    public function it_equals_regex_with_same_matches()
    {
        self::assertTrue(RegexEqualityChecker::equals('^foo(bar)$', '#^foobar$#'));
        self::assertTrue(RegexEqualityChecker::equals('^(foo)(bar)$', '#^foobar$#'));
        self::assertTrue(RegexEqualityChecker::equals('^(foo)(bar)?$', '#^foobar$#'));
        self::assertTrue(RegexEqualityChecker::equals('^(foo)(bar)?$', '#^foob?ar$#'));
        self::assertTrue(RegexEqualityChecker::equals('^(foo)(bar)?$', '#^foo[b]?ar$#'));
        self::assertTrue(RegexEqualityChecker::equals('^(foo)(bar)?$', '#^foo[ba]?ar$#'));
        self::assertTrue(RegexEqualityChecker::equals('^(foo)(bar)?$', '#foob(?=ar)#'));
        self::assertTrue(RegexEqualityChecker::equals('^foo?$', '#foo(?!bar)#'));
        self::assertTrue(RegexEqualityChecker::equals('^foo?$', '#foo(?!bar)#'));
        self::assertTrue(RegexEqualityChecker::equals('^/(?P<tld>app)/$', '#^/app/$#'));
    }

    /**
     * @test
     */
    public function it_does_not_equal_for_regex_with_different_matches()
    {
        self::assertFalse(RegexEqualityChecker::equals('foo(?!bar)', '#foobar#'));
        self::assertFalse(RegexEqualityChecker::equals('^foo$', '#^foobar$#'));
        self::assertFalse(RegexEqualityChecker::equals('^foobar$', '#^foo$#'));
    }
}
