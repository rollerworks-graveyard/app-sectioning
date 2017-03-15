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

namespace Rollerworks\Bundle\AppSectioning\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * The SectioningConfigurator allows to add sections to your
 * Bundle's Configuration main tree.
 */
final class SectioningConfigurator
{
    private function __construct()
    {
        // no-op, this class should not be initialized.
    }

    /**
     * Create a new app-section configuration ArrayNode.
     *
     * The returned value needs to be added to your Configuration
     * tree using append().
     *
     * @param string $name
     *
     * @return NodeDefinition
     *
     * @see https://symfony.com/doc/current/components/config/definition.html#appending-sections
     */
    public static function createSection(string $name): NodeDefinition
    {
        $node = (new TreeBuilder())->root($name);
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('prefix')
                    ->defaultValue('/')
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifTrue(function ($v) {
                            return preg_match('#[{}]#', $v);
                        })
                        ->then(function () {
                            throw new \InvalidArgumentException(
                                'Placeholders in the "prefix" are not supported yet.'
                            );
                        })
                    ->end()
                ->end()
                ->scalarNode('host')
                    ->defaultValue(null)
                    ->validate()
                        ->ifTrue(function ($v) {
                            return preg_match('#[{}]#', $v);
                        })
                        ->then(function () {
                            throw new \InvalidArgumentException(
                                'Placeholders in the "host" are not supported yet.'
                            );
                        })
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
