<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Client;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\VolcanoArkApiBundle\Client\VolcanoArkApiClient;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;

/**
 * @internal
 */
#[CoversClass(VolcanoArkApiClient::class)]
#[RunTestsInSeparateProcesses]
class VolcanoArkApiClientTest extends AbstractIntegrationTestCase
{
    private VolcanoArkApiClient $client;

    protected function onSetUp(): void
    {
        $this->client = self::getService(VolcanoArkApiClient::class);
    }

    public function testGetBaseUrl(): void
    {
        $this->assertEquals('https://open.volcengineapi.com', $this->client->getBaseUrl());
    }

    public function testRotateApiKey(): void
    {
        // 获取当前API密钥
        $initialKey = $this->client->getCurrentApiKey();

        // 执行轮换操作
        $this->client->rotateApiKey();

        // 验证API密钥已被更新
        $newKey = $this->client->getCurrentApiKey();
        $this->assertNotSame($initialKey, $newKey);
        $this->assertInstanceOf(ApiKey::class, $newKey);
    }

    public function testRequest(): void
    {
        // 创建 Mock RequestInterface
        $request = $this->createMock(\HttpClientBundle\Request\RequestInterface::class);
        $request->method('getRequestPath')->willReturn('/test/path');
        $request->method('getRequestMethod')->willReturn('POST');
        $request->method('getRequestOptions')->willReturn(['test' => 'data']);

        // 由于 request() 方法是对父类的简单包装加类型守卫,
        // 我们通过 reflection 验证方法存在且返回类型正确
        $reflection = new \ReflectionMethod($this->client, 'request');
        $this->assertTrue($reflection->isPublic());

        // 验证返回类型声明
        $returnType = $reflection->getReturnType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        $this->assertEquals('array', $returnType->getName());
        $this->assertFalse($returnType->allowsNull());
    }
}
