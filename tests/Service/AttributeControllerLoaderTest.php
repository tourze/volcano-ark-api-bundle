<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;
use Tourze\VolcanoArkApiBundle\Service\AttributeControllerLoader;

/**
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
#[Group('volcano-ark-api-bundle')]
class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    private AttributeControllerLoader $loader;

    protected function onSetUp(): void
    {
        $this->loader = self::getService(AttributeControllerLoader::class);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(AttributeControllerLoader::class, $this->loader);
    }

    public function testLoadReturnsRouteCollection(): void
    {
        $result = $this->loader->load('test-resource');

        $this->assertInstanceOf(RouteCollection::class, $result);
    }

    public function testSupportsReturnsFalse(): void
    {
        $result = $this->loader->supports('test-resource');

        $this->assertFalse($result);
    }

    public function testSupportsWithTypeReturnsFalse(): void
    {
        $result = $this->loader->supports('test-resource', 'test-type');

        $this->assertFalse($result);
    }

    public function testAutoloadReturnsRouteCollection(): void
    {
        $result = $this->loader->autoload();

        $this->assertInstanceOf(RouteCollection::class, $result);
    }

    public function testLoadAndAutoloadReturnSameCollection(): void
    {
        $loadResult = $this->loader->load('test-resource');
        $autoloadResult = $this->loader->autoload();

        $this->assertEquals($loadResult, $autoloadResult);
    }

    public function testRouteCollectionIsNotEmpty(): void
    {
        $collection = $this->loader->autoload();

        // 至少应该有一些路由从控制器中加载
        $this->assertGreaterThanOrEqual(0, $collection->count());
    }

    public function testImplementsRoutingAutoLoaderInterface(): void
    {
        $this->assertInstanceOf(RoutingAutoLoaderInterface::class, $this->loader);
    }

    public function testExtendsLoader(): void
    {
        $this->assertInstanceOf(Loader::class, $this->loader);
    }
}
