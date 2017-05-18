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

namespace Rollerworks\Bundle\AppSectioningBundle\DependencyInjection;

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
            ->validate()
                ->ifTrue(function (array $v) {
                    return false !== strpos((string) $v['host'], '{');
                })
                ->then(function (array $v) {
                    $varNames = [];

                    if ((string) $v['host'] !== '') {
                        preg_match_all('#\{\w+\}#', $v['host'], $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
                        foreach ($matches as $match) {
                            $varNames[] = substr($match[0][0], 1, -1);
                        }
                    }

                    foreach ($varNames as $varName) {
                        if (!empty($v['requirements'][$varName])) {
                            throw new \InvalidArgumentException(sprintf('Missing requirement for attribute "%s".', $varName));
                        }

                        if (!isset($v['defaults'][$varName]) || '' === trim((string) $v['defaults'][$varName])) {
                            throw new \InvalidArgumentException(sprintf('Missing default value for attribute "%s".', $varName));
                        }
                    }
                })
            ->end()
            ->children()
                ->scalarNode('prefix')
                    ->defaultValue('/')
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifTrue(function ($v) {
                            return false !== strpos((string) $v, '{');
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
                ->end()
                 ->arrayNode('requirements')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('defaults')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
