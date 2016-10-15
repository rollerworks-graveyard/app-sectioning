<?php

/*
 * This file is part of the Rollerworks AppSectioningBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\AppSectioning;

use Rollerworks\Bundle\AppSectioning\Exception\ValidatorException;

/**
 * The AppSectionsValidator validates whether the provided Application sections
 * information is valid.
 *
 * In practice this validates that all provided sections are not conflicting
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
     * Validates the provided configuration for duplicated prefixes in the same host group.
     *
     * @throws ValidatorException When one ore more sections have a conflicting configuration.
     */
    public function validate(): bool
    {
        $prefixes = [];
        $conflicts = [];

        foreach ($this->sections as $name => $config) {
            $prefix = $config['prefix'];
            $host = $config['host'];

            if ($this->isPrefixAlreadyUsed($prefixes, $host, $prefix)) {
                $conflicts[$prefixes[$host][$prefix]][] = $name;
            } else {
                $prefixes[$host][$prefix] = $name;
            }
        }

        if (count($conflicts)) {
            throw ValidatorException::sectionsConfigConflict($this->formatPrefixConflicts($conflicts));
        }

        return true;
    }

    private function formatPrefixConflicts(array $conflicts): array
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

    private function isPrefixAlreadyUsed(array $prefixes, $host, $prefix)
    {
        return isset($prefixes[$host][$prefix]);
    }
}
