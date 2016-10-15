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

namespace Rollerworks\Bundle\AppSectioning\DependencyInjection\Compiler;

use Rollerworks\Bundle\AppSectioning\AppSectionsValidator;
use Rollerworks\Bundle\AppSectioning\DependencyInjection\SectioningFactory;
use Rollerworks\Bundle\AppSectioning\SectionsConfigurator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AppSectionsPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $sectionConfigServiceIds = $container->findTaggedServiceIds(SectioningFactory::TAG_NAME);

        if (0 === count($sectionConfigServiceIds)) {
            return;
        }

        $this->processSections($container, $sectionConfigServiceIds);
    }

    private function processSections(ContainerBuilder $container, array $configServiceIds)
    {
        $configurator = new SectionsConfigurator();
        $validator = new AppSectionsValidator();

        foreach ($configServiceIds as $serviceId => list($tag)) {
            $service = $container->get($serviceId);

            $validator->set($tag['section_name'], $service);
            $configurator->set($tag['section_name'], $service, $tag['service_prefix']);
        }

        $validator->validate();

        $configurator->registerToContainer($container);

        $routeLoader = $container->findDefinition('park_manager.app_section.route_loader');
        $routeLoader->replaceArgument(1, $configurator->exportConfiguration());
    }
}
