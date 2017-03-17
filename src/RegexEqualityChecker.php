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

namespace Rollerworks\Bundle\AppSectioning;

use ReverseRegex\Generator\Scope;
use ReverseRegex\Lexer;
use ReverseRegex\Parser;
use ReverseRegex\Random\SimpleRandom;

/**
 * @internal
 */
final class RegexEqualityChecker
{
    public static function equals(string $first, string $second): bool
    {
        if ($first === $second) {
            return true;
        }

        $first = preg_replace('/\(\?P<[a-z_0-9]+>/', '(', $first);
        $parser = new Parser(new Lexer($first), new Scope(), new Scope());
        $random = new SimpleRandom(1);

        for ($i = 1; $i < 10; ++$i) {
            $random->seed($i);
            $result = '';

            if (preg_match($second, $parser->parse()->getResult()->generate($result, $random))) {
                return true;
            }
        }

        return false;
    }
}
