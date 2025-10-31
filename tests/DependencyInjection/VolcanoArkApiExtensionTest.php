<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\VolcanoArkApiBundle\Client\VolcanoArkApiClient;
use Tourze\VolcanoArkApiBundle\DependencyInjection\VolcanoArkApiExtension;

/**
 * @internal
 */
#[CoversClass(VolcanoArkApiExtension::class)]
class VolcanoArkApiExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private VolcanoArkApiExtension $extension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new VolcanoArkApiExtension();
    }

    public function testLoadServicesInProductionEnvironment(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $this->extension->load([], $container);

        // 验证容器中的服务是否被正确注册
        $this->assertTrue($container->hasDefinition(VolcanoArkApiClient::class));
    }

    public function testLoadServicesInDevelopmentEnvironment(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'dev');

        $this->extension->load([], $container);

        // 验证开发环境的服务被加载
        $this->assertTrue($container->hasDefinition(VolcanoArkApiClient::class));
    }

    public function testLoadServicesInTestEnvironment(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $this->extension->load([], $container);

        // 验证测试环境的服务被加载
        $this->assertTrue($container->hasDefinition(VolcanoArkApiClient::class));
    }

    public function testLoadWithEmptyConfigs(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $this->extension->load([], $container);

        // 验证即使没有配置，基础服务也会被加载
        $this->assertGreaterThan(0, count($container->getDefinitions()));
    }

    public function testLoadWithMultipleConfigs(): void
    {
        $configs = [
            ['some_config_key' => 'value1'],
            ['another_config_key' => 'value2'],
        ];

        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $this->extension->load($configs, $container);

        // 验证服务被正确加载
        $this->assertGreaterThan(0, count($container->getDefinitions()));
    }

    public function testLoadInCustomEnvironment(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'staging');

        $this->extension->load([], $container);

        // 验证自定义环境不会加载特殊配置文件，但基础服务仍会加载
        $this->assertGreaterThan(0, count($container->getDefinitions()));
    }

    public function testExtensionAlias(): void
    {
        $this->assertEquals('volcano_ark_api', $this->extension->getAlias());
    }

    public function testLoadedDefinitionsContainExpectedServices(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $this->extension->load([], $container);

        $definitions = $container->getDefinitions();
        $aliases = $container->getAliases();

        // 验证某些预期的服务类型存在
        $serviceTypes = [
            'client',
            'service',
            'repository',
        ];

        $foundServices = [];
        foreach (array_merge(array_keys($definitions), array_keys($aliases)) as $serviceId) {
            if (str_contains($serviceId, 'VolcanoArk') || str_contains($serviceId, 'volcano_ark_api')) {
                $foundServices[] = $serviceId;
            }
        }

        $this->assertNotEmpty($foundServices, 'Should have volcano_ark_api services defined');
    }

    public function testEnvironmentSpecificConfigLoading(): void
    {
        $environments = ['dev', 'test', 'prod'];

        foreach ($environments as $env) {
            $container = new ContainerBuilder();
            $container->setParameter('kernel.environment', $env);

            $this->extension->load([], $container);

            // 每个环境都应该有基础服务
            $this->assertGreaterThan(0, count($container->getDefinitions()), "Environment {$env} should load services");
        }
    }

    public function testParametersAreSet(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $this->extension->load([], $container);

        // 验证容器是否包含一些基本的参数或定义
        $hasVolcanoArkServices = false;
        foreach ($container->getDefinitions() as $id => $definition) {
            if (str_contains($id, 'VolcanoArk') || str_contains($id, 'volcano_ark_api')) {
                $hasVolcanoArkServices = true;
                break;
            }
        }

        foreach ($container->getAliases() as $id => $alias) {
            if (str_contains($id, 'VolcanoArk') || str_contains($id, 'volcano_ark_api')) {
                $hasVolcanoArkServices = true;
                break;
            }
        }

        $this->assertTrue($hasVolcanoArkServices, 'Should have volcano ark related services');
    }
}
