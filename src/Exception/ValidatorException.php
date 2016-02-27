<?php

namespace ParkManager\Component\AppSectioning\Exception;

final class ValidatorException extends \LogicException
{
    public static function sectionsConfigConflict($failedSections)
    {
        $errors = [];

        foreach ($failedSections as $name => list($host, $prefix, $conflicts)) {
            $errors[] = sprintf(
                'AppSection(s) "%s" conflict with "%s", all have the same host "%s" and prefix "%s" configured.',
                implode('", "', $conflicts),
                $name,
                $host,
                $prefix
            );
        }

        return new self(implode("\n", $errors));
    }
}
