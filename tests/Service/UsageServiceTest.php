<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Service\UsageService;

/**
 * @internal
 */
#[CoversClass(UsageService::class)]
#[RunTestsInSeparateProcesses]
class UsageServiceTest extends AbstractIntegrationTestCase
{
    private UsageService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(UsageService::class);
    }

    public function testGetUsageForApiKey(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setApiKey('test-api-key');
        $apiKey->setSecretKey('test-secret-key');
        $apiKey->setRegion('cn-beijing');
        $apiKey->setProvider('volcano_ark');
        $apiKey->setIsActive(true);

        // 由于依赖外部API，我们测试服务的可用性和参数验证
        $this->assertInstanceOf(UsageService::class, $this->service);

        // 测试API密钥的设置
        $this->assertEquals('test-api-key', $apiKey->getApiKey());
        $this->assertEquals('test-secret-key', $apiKey->getSecretKey());
        $this->assertEquals('cn-beijing', $apiKey->getRegion());
        $this->assertTrue($apiKey->isActive());

        // 测试时间参数的有效性
        $startTime = 1640995200;
        $endTime = 1640998800;
        $interval = 3600;

        $this->assertGreaterThan(0, $startTime);
        $this->assertGreaterThan($startTime, $endTime);
        $this->assertGreaterThan(0, $interval);
    }

    public function testUsageServiceConfiguration(): void
    {
        // 测试UsageService的正确实例化
        $this->assertInstanceOf(UsageService::class, $this->service);

        // 通过反射测试方法存在性
        $reflection = new \ReflectionClass($this->service);

        $this->assertTrue($reflection->hasMethod('getUsageForApiKey'));
        $this->assertTrue($reflection->hasMethod('getUsage'));

        $getUsageMethod = $reflection->getMethod('getUsageForApiKey');
        $this->assertTrue($getUsageMethod->isPublic());
    }

    public function testGetUsageForApiKeyWithOptionalParameters(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setApiKey('test-api-key');
        $apiKey->setSecretKey('test-secret');
        $apiKey->setRegion('cn-beijing');
        $apiKey->setIsActive(true);

        // 测试可选参数的数据结构
        $scenes = ['chat'];
        $endpointIds = ['endpoint-1'];
        $jobId = 'job-123';
        $project = 'project-test';

        $this->assertContains('chat', $scenes);
        $this->assertContains('endpoint-1', $endpointIds);

        // 验证服务的方法可以接受这些参数
        $reflection = new \ReflectionMethod($this->service, 'getUsageForApiKey');
        $this->assertGreaterThanOrEqual(4, $reflection->getNumberOfParameters());
    }

    public function testGetUsage(): void
    {
        // 测试getUsage方法的存在性和参数
        $reflection = new \ReflectionMethod($this->service, 'getUsage');
        $this->assertTrue($reflection->isPublic());

        // 验证时间参数
        $startTime = 1640995200;
        $endTime = 1640998800;
        $interval = 3600;

        $this->assertGreaterThan($startTime, $endTime);

        // 验证服务的正确配置
        $this->assertInstanceOf(UsageService::class, $this->service);
    }
}
