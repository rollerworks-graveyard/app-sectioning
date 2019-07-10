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

namespace Rollerworks\Component\AppSectioning\Tests\Functional\Application;

use Rollerworks\Component\AppSectioning\SectioningFactory;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class AppKernel extends Kernel
{
    use MicroKernelTrait;

    private $config;

    public function __construct($config, $debug = true)
    {
        if (!(new Filesystem())->isAbsolutePath($config)) {
            $config = __DIR__.'/config/'.$config;
        }

        if (!file_exists($config)) {
            throw new \RuntimeException(sprintf('The config file "%s" does not exist.', $config));
        }

        $this->config = $config;

        parent::__construct('test', $debug);
    }

    public function getName()
    {
        return 'AppSectioning'.substr(sha1($this->config), 0, 3);
    }

    public function registerBundles()
    {
        $bundles = [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new AppBundle\AppBundle(),
        ];

        return $bundles;
    }

    public function getRootDir()
    {
        if (null === $this->rootDir) {
            $this->rootDir = str_replace('\\', '/', __DIR__);
        }

        return $this->rootDir;
    }

    public function getCacheDir()
    {
        return (getenv('TMPDIR') ?: sys_get_temp_dir()).'/AppSectioning/'.substr(sha1($this->config), 0, 6);
    }

    public function serialize()
    {
        return serialize([$this->config, $this->isDebug()]);
    }

    public function unserialize($str)
    {
        \call_user_func_array([$this, '__construct'], unserialize($str));
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $loader->load($this->config);

        $sections = $container->getParameter('app_sections');
        (new SectioningFactory($container, 'park_manager.section'))
            ->set('backend', $sections['backend'])
            ->set('frontend', $sections['frontend'])
            ->register();
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $routes->import(__DIR__.'/config/routing.yml');
    }
}
