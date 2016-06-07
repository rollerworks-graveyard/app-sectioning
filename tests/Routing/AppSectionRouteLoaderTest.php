<?php

/*
 * This file is part of the Park-Manager AppSectioningBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ParkManager\Bundle\AppSectioning\Tests\Routing;

use ParkManager\Bundle\AppSectioning\Routing\AppSectionRouteLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class AppSectionRouteLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AppSectionRouteLoader
     */
    private $loader;

    const APP_SECTIONS = [
        'api' => ['prefix' => 'api/'],
        'frontend' => ['prefix' => '/'],
        'backend' => [
            'prefix' => '/',
            'host' => 'example.com',
            'requirements' => ['host' => 'example\.com'],
        ],
    ];

    /**
     * @before
     */
    public function createLoader()
    {
        $loader = $this->prophesize(LoaderInterface::class);

        // type=null
        $loader->supports('something.yml', null)->willReturn(true);
        $loader->load('something.yml', null)->will(
            function () {
                $routeCollection = new RouteCollection();
                $routeCollection->add('frontend_news', new Route('news/'));
                $routeCollection->add('frontend_blog', new Route('blog/'));

                return $routeCollection;
            }
        );

        // type=xml
        $loader->supports('something.xml', 'xml')->willReturn(true);
        $loader->load('something.xml', 'xml')->will(
            function () {
                $routeCollection = new RouteCollection();
                $routeCollection->add('backend_main', new Route('/'));
                $routeCollection->add('backend_user', new Route('user/'));

                return $routeCollection;
            }
        );

        // type=php; unsupported, so no need to call load()
        $loader->supports('something.php', 'php')->willReturn(false);

        $this->loader = new AppSectionRouteLoader($loader->reveal(), self::APP_SECTIONS);
    }

    /**
     * @test
     * @dataProvider provideSupported
     */
    public function it_returns_true_when_its_supported($resource)
    {
        $this->assertTrue($this->loader->supports($resource, 'app_section'));
    }

    public function provideSupported()
    {
        return [
            'Resource without type' => ['frontend#something.yml'],
            'Resource with type' => ['frontend:xml#something.xml'],
            'Resource in other section' => ['api#something.yml'],
        ];
    }

    /**
     * @test
     * @dataProvider provideUnsupported
     */
    public function it_returns_false_when_its_not_supported($resource, $type = 'app_section')
    {
        $this->assertFalse($this->loader->supports($resource, $type));
    }

    public function provideUnsupported()
    {
        return [
            'Wrong type (empty)' => ['something.yml', null],
            'Wrong type (yml)' => ['something.yml', 'yml'],
        ];
    }

    /**
     * @test
     */
    public function it_loads_routing_with_config_applied()
    {
        $routeCollection1 = new RouteCollection();
        $routeCollection1->add('frontend_news', new Route('news/'));
        $routeCollection1->add('frontend_blog', new Route('blog/'));

        $routeCollection2 = new RouteCollection();
        $routeCollection2->add('backend_main', new Route('/', ['host' => 'example.com'], ['host' => 'example\.com'], [], '{host}'));
        $routeCollection2->add('backend_user', new Route('user/', ['host' => 'example.com'], ['host' => 'example\.com'], [], '{host}'));

        $routeCollection3 = new RouteCollection();
        $routeCollection3->add('frontend_news', new Route('api/news/'));
        $routeCollection3->add('frontend_blog', new Route('api/blog/'));

        $this->assertEquals($routeCollection1, $this->loader->load('frontend#something.yml'));
        $this->assertEquals($routeCollection2, $this->loader->load('backend:xml#something.xml'));
        $this->assertEquals($routeCollection3, $this->loader->load('api#something.yml'));
        $this->assertEquals($routeCollection3, $this->loader->load('api#something.yml'));
    }

    /**
     * @test
     * @dataProvider provideInvalid
     */
    public function it_throws_an_exception_when_the_resource_is_invalid($value)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                'This is not a valid section resource "%s", '.
                'expected format is "section-name#actual-resource" or "section-name:type#actual-resource".',
                $value
            )
        );

        $this->loader->load($value);
    }

    public function provideInvalid()
    {
        return [
            'Wrong format (missing # and type)' => ['frontend:something.xml'],
            'Wrong format (missing type value)' => ['frontend:#something.xml'],
        ];
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_the_section_is_unregistered()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No section was registered with name "foo"');

        $this->loader->load('foo#something.yml');
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_the_attempting_to_import_another_section()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to import app-section route collection with type "app_section".');

        $this->loader->load('api:app_section#frontend');
    }
}
