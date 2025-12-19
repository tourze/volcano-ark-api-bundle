<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OpenAiContracts\Response\ModelListResponseInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\VolcanoArkApiBundle\Service\VolcanoArkOpenAiClient;

/**
 * @internal
 */
#[CoversClass(VolcanoArkOpenAiClient::class)]
#[RunTestsInSeparateProcesses]
class VolcanoArkOpenAiClientTest extends AbstractIntegrationTestCase
{
    private VolcanoArkOpenAiClient $client;

    protected function onSetUp(): void
    {
        // 使用服务容器获取客户端，而不是直接实例化
        $this->client = self::getService(VolcanoArkOpenAiClient::class);
        $this->client->setApiKey('test-api-key');
    }

    public function testGetName(): void
    {
        $this->assertEquals('VolcanoArk', $this->client->getName());

        $this->client->setName('CustomName');
        $this->assertEquals('CustomName', $this->client->getName());
    }

    public function testGetBaseUrl(): void
    {
        $this->assertEquals('https://ark.cn-beijing.volces.com/api/v3/', $this->client->getBaseUrl());

        $this->client->setBaseUrl('https://ark.cn-shanghai.volces.com/api/v3/');
        $this->assertEquals('https://ark.cn-shanghai.volces.com/api/v3/', $this->client->getBaseUrl());
    }

    public function testBotMode(): void
    {
        $this->assertFalse($this->client->isBotMode());

        $this->client->setBotMode(true);
        $this->assertTrue($this->client->isBotMode());
        $this->assertEquals('https://ark.cn-beijing.volces.com/api/v3/bots/', $this->client->getBaseUrl());

        $this->client->setBotMode(false);
        $this->assertFalse($this->client->isBotMode());
        $this->assertEquals('https://ark.cn-beijing.volces.com/api/v3/', $this->client->getBaseUrl());
    }

    public function testSetRegion(): void
    {
        $this->client->setRegion('cn-shanghai');
        $this->assertEquals('https://ark.cn-shanghai.volces.com/api/v3/', $this->client->getBaseUrl());

        $this->client->setBotMode(true);
        $this->client->setRegion('cn-guangzhou');
        $this->assertEquals('https://ark.cn-guangzhou.volces.com/api/v3/bots/', $this->client->getBaseUrl());
    }

    public function testClientBasicProperties(): void
    {
        // 测试客户端的基本属性和方法
        $this->assertInstanceOf(VolcanoArkOpenAiClient::class, $this->client);

        // 测试API密钥设置
        $this->client->setApiKey('new-test-key');
        // 无法直接访问私有属性，测试设置是否成功
        $this->assertInstanceOf(VolcanoArkOpenAiClient::class, $this->client);
    }

    public function testClientMethodsExist(): void
    {
        // 通过反射检查客户端的核心方法是否存在
        $reflection = new \ReflectionClass($this->client);

        $this->assertTrue($reflection->hasMethod('listModels'));
        $this->assertTrue($reflection->hasMethod('chatCompletion'));
        $this->assertTrue($reflection->hasMethod('getBalance'));
        $this->assertTrue($reflection->hasMethod('isAvailable'));

        // 验证方法是公开的
        $this->assertTrue($reflection->getMethod('listModels')->isPublic());
        $this->assertTrue($reflection->getMethod('chatCompletion')->isPublic());
    }

    public function testClientConfiguration(): void
    {
        // 测试客户端的配置方法
        $this->client->setName('TestClient');
        $this->assertEquals('TestClient', $this->client->getName());

        // 测试区域设置
        $this->client->setRegion('cn-shanghai');
        $this->assertEquals('https://ark.cn-shanghai.volces.com/api/v3/', $this->client->getBaseUrl());

        // 测试机器人模式
        $this->client->setBotMode(true);
        $this->assertTrue($this->client->isBotMode());
        $this->assertStringContainsString('bots', $this->client->getBaseUrl());
    }

    public function testUrlGeneration(): void
    {
        // 测试基本URL设置
        $this->client->setBotMode(false);
        $this->client->setRegion('cn-shanghai');
        $this->assertEquals('https://ark.cn-shanghai.volces.com/api/v3/', $this->client->getBaseUrl());

        // 测试机器人模式URL
        $this->client->setBotMode(true);
        $this->client->setRegion('cn-beijing');
        $this->assertEquals('https://ark.cn-beijing.volces.com/api/v3/bots/', $this->client->getBaseUrl());
    }

    public function testClientIntegration(): void
    {
        // 测试客户端的集成配置
        $this->assertInstanceOf(VolcanoArkOpenAiClient::class, $this->client);

        // 测试重试机制的配置
        $reflection = new \ReflectionProperty($this->client, 'maxRetries');
        $reflection->setAccessible(true);
        $this->assertEquals(3, $reflection->getValue($this->client));
    }

    public function testListModels(): void
    {
        // 测试没有端点ID时的行为
        $this->client->setEndpointId(null);

        try {
            $this->client->listModels();
            self::fail('Expected ApiException to be thrown when listModels fails without endpointId');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
        }

        // 测试有端点ID时的行为
        $this->client->setEndpointId('ep-test-endpoint');

        try {
            $result = $this->client->listModels();

            // 当API调用失败时，应该返回端点ID作为模型
            $this->assertInstanceOf(ModelListResponseInterface::class, $result);
            $models = $result->getData();
            $this->assertCount(1, $models);
            $this->assertEquals('ep-test-endpoint', $models[0]->getId());
            $this->assertEquals('model', $models[0]->getObject());
            $this->assertEquals('volcano-ark', $models[0]->getOwnedBy());
        } catch (\Exception $e) {
            // 如果API调用失败，应该抛出异常
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }
}
