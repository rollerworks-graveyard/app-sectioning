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

namespace Rollerworks\Component\AppSectioning\Tests\Functional;

use Rollerworks\Component\AppSectioning\Tests\Functional\Application\AppKernel;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate;

abstract class FunctionalTestCase extends WebTestCase
{
    protected static function createKernel(array $options = []): AppKernel
    {
        return new AppKernel($options['config'] ?? 'default.yml');
    }

    protected static function getKernelClass(): string
    {
        return AppKernel::class;
    }

    protected static function newClient(array $options = [], array $server = []): Client
    {
        $client = static::createClient(array_merge(['config' => 'default.yml'], $options), $server);

        /** @var CacheWarmerAggregate $warmer */
        $warmer = $client->getContainer()->get('cache_warmer');
        $warmer->warmUp($client->getContainer()->getParameter('kernel.cache_dir'));
        $warmer->enableOptionalWarmers();

        return $client;
    }
}
