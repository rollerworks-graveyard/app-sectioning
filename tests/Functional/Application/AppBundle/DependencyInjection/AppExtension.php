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

use Rollerworks\Bundle\AppSectioningBundle\DependencyInjection\SectioningFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class AppExtension extends Extension
{
    const EXTENSION_ALIAS = 'test_app';

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $factory = new SectioningFactory($container, 'acme.section');
        foreach ($config['sections'] as $section => $sectionConfig) {
            $factory->set($section, $sectionConfig);
        }
    }

    public function getAlias(): string
    {
        return self::EXTENSION_ALIAS;
    }
}
