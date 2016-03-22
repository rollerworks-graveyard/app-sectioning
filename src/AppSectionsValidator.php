<?php

/*
 * This file is part of the Park-Manager AppSectioningBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ParkManager\Bundle\AppSectioning;

use ParkManager\Bundle\AppSectioning\Exception\ValidatorException;

/**
 * The AppSectionsValidator validates whether the provided Application sections
 * information is valid.
 *
 * The in practice this validates that all provided sections are not conflicting
 * with each other (unique prefix per host).
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class AppSectionsValidator
{
    /**
     * @var array[]
     */
    private $sections = [];

    /**
     * Set a section to validate as a whole list.
     *
     * @param string               $name
     * @param SectionConfiguration $config
     */
    public function set(string $name, SectionConfiguration $config)
    {
        $this->sections[$name] = $config->getConfig();
    }

    /**
     * Validates the provided configuration.
     *
     * @param bool $exceptionOnFailure
     *
     * @throws ValidatorException When one ore more sections have a wrong or conflicting
     *                            configuration.
     *
     * @return bool Returns true on success, false when ($exceptionOnFailure is true) and there
     *              are violations.
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
        } catch (ValidatorException $e) {
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
