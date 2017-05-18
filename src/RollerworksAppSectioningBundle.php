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

namespace Rollerworks\Bundle\AppSectioningBundle;

use Rollerworks\Bundle\AppSectioningBundle\DependencyInjection\AppSectionExtension;
use Rollerworks\Bundle\AppSectioningBundle\DependencyInjection\Compiler\AppSectionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class RollerworksAppSectioningBundle extends Bundle
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
