<?php

/*
 * This file is part of the ParkManager AppSectioning package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ParkManager\Component\AppSectioning;

use ParkManager\Component\AppSectioning\Exception\ValidatorException;

/**
 * The AppSectionsValidator validates whether the provided Application sections
 * information is valid.
 *
 * The following is validated:
 *
 * * the host_pattern matches the host (when both are provided) in a section;
 * * all provided sections are not conflicting with each other (unique prefix per host);
 *
 * @todo Validate when a host-pattern is provided, other matching hosts do conflict with prefixes.
 */
final class AppSectionsValidator
{
    /**
     * @var array[]
     */
    private $sections = [];

    const DEFAULT_VALUES = [
        'prefix' => '/',
        'host' => null,
    ];

    /**
     * Set a section to validate as a whole list.
     *
     * @param string $name
     * @param array  $config
     */
    public function set(string $name, array $config)
    {
        if (isset($config['prefix'])) {
            $config['prefix'] = mb_strtolower($config['prefix']);
        }

        if (isset($config['host'])) {
            $config['host'] = mb_strtolower($config['host']);
        }

        $this->sections[$name] = array_merge(self::DEFAULT_VALUES, $config);
    }

    /**
     * Validates the provided configuration.
     *
     * @param bool $exceptionOnFailure
     *
     * @return bool Returns true on success, false when ($exceptionOnFailure is true) and there
     *              are violations.
     *
     * @throws ValidatorException When one ore more sections have a wrong or conflicting
     *                            configuration.
     */
    public function validate(bool $exceptionOnFailure = true): bool
    {
        $prefixes = [];
        $conflicts = [];

        try {
            foreach ($this->sections as $name => $config) {
                $prefix = $config['prefix'];
                $host = $config['host'];

                if (isset($prefixes[$host][$prefix])) {
                    $conflicts[$prefixes[$host][$prefix]][] = $name;
                } else {
                    $prefixes[$host][$prefix] = $name;
                }
            }

            if (count($conflicts)) {
                throw ValidatorException::sectionsConfigConflict($this->buildConflictsArray($conflicts));
            }
        }
        catch (ValidatorException $e) {
            if (!$exceptionOnFailure) {
                return false;
            }

            throw $e;
        }

        return true;
    }

    private function buildConflictsArray(array $conflicts): array
    {
        $failedSections = [];

        foreach ($conflicts as $primary => $sections) {
            $failedSections[$primary] = [
                $this->sections[$primary]['host'],
                $this->sections[$primary]['prefix'],
                $sections,
            ];
        }

        return $failedSections;
    }
}
