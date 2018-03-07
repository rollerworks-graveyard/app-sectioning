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

namespace Rollerworks\Component\AppSectioning\Exception;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class ValidatorException extends \LogicException
{
    public static function sectionsConfigConflict($failedSections)
    {
        $errors = [];

        foreach ($failedSections as $name => list($host, $prefix, $conflicts)) {
            $errors[] = sprintf(
                'AppSection(s) "%s" conflict with "%s", all match host pattern "%s" and prefix "%s".',
                implode('", "', $conflicts),
                $name,
                $host,
                $prefix
            );
        }

        return new self(implode("\n", $errors));
    }
}
