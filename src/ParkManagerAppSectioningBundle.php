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

use ParkManager\Bundle\AppSectioning\DependencyInjection\AppSectionExtension;
use ParkManager\Bundle\AppSectioning\DependencyInjection\Compiler\AppSectionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ParkManagerAppSectioningBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AppSectionsPass());
    }

    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = new AppSectionExtension();
        }

        return $this->extension;
    }

    protected function getContainerExtensionClass()
    {
        return AppSectionExtension::class;
    }
}
