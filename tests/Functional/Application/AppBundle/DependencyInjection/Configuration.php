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

namespace Rollerworks\Bundle\AppSectioningBundle\Tests\Functional\Application\AppBundle\DependencyInjection;

use Rollerworks\Bundle\AppSectioningBundle\DependencyInjection\SectioningConfigurator;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(AppExtension::EXTENSION_ALIAS);

        $rootNode
            ->children()
                ->arrayNode('sections')
                    ->children()
                        ->append(SectioningConfigurator::createSection('backend'))
                        ->append(SectioningConfigurator::createSection('frontend'))
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
