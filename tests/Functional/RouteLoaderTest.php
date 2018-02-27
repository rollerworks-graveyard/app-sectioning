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

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class RouteLoaderTest extends FunctionalTestCase
{
    /**
     * @dataProvider provideExpectedImportedRoutes
     */
    public function testRoutesToImportedRoutes($uri, $expectedRoute)
    {
        $client = self::newClient();

        $client->request('GET', 'http://example.com'.$uri);
        $this->assertEquals('Route: '.$expectedRoute, $client->getResponse()->getContent());
    }

    public function testFailsWithNonImportedRoutes()
    {
        $client = self::newClient();

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('/noop/products/list');

        $client->request('GET', '/noop/products/list');
    }

    public function provideExpectedImportedRoutes(): array
    {
        return [
            'frontend_products_show' => ['/products/show', 'frontend_products_show'],
            'frontend_products_search' => ['/products/search', 'frontend_products_search'],

            'backend_products_list' => ['/backend/products/list', 'backend_products_list'],
            'backend_products_show' => ['/backend/products/show/1', 'backend_products_show'],
        ];
    }
}
