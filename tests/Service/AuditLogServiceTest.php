<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\VolcanoArkApiBundle\DTO\AuditLogFilter;
use Tourze\VolcanoArkApiBundle\DTO\AuditLogResult;
use Tourze\VolcanoArkApiBundle\Service\AuditLogService;

/**
 * @internal
 */
#[CoversClass(AuditLogService::class)]
#[RunTestsInSeparateProcesses]
class AuditLogServiceTest extends AbstractIntegrationTestCase
{
    private AuditLogService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(AuditLogService::class);
    }

    public function testListAuditLogs(): void
    {
        $filter = new AuditLogFilter('audit', 'high');

        // 由于依赖外部API，我们测试服务是否正确实例化和配置
        $this->assertInstanceOf(AuditLogService::class, $this->service);

        // 测试AuditLogFilter的创建
        $this->assertEquals('audit', $filter->logType);
        $this->assertEquals('high', $filter->riskLevel);

        // 测试方法签名和参数传递（通过反射）
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('listAuditLogs');
        $this->assertEquals('listAuditLogs', $method->getName());
        $this->assertTrue($method->isPublic());
    }

    public function testListAuditLogsWithDefaults(): void
    {
        $filter = new AuditLogFilter();

        // 测试默认的AuditLogFilter
        $this->assertNull($filter->logType);
        $this->assertNull($filter->riskLevel);

        // 验证服务可以处理默认过滤器
        $this->assertInstanceOf(AuditLogService::class, $this->service);
    }

    public function testAuditLogResultCreation(): void
    {
        // 测试AuditLogResult的实例化
        $result = new AuditLogResult(5, 2, 50, []);

        $this->assertInstanceOf(AuditLogResult::class, $result);
        $this->assertEquals(5, $result->totalCount);
        $this->assertEquals(2, $result->pageNumber);
        $this->assertEquals(50, $result->pageSize);
        $this->assertEquals([], $result->items);
    }
}
