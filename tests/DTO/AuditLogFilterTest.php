<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\VolcanoArkApiBundle\DTO\AuditLogFilter;

/**
 * @internal
 */
#[CoversClass(AuditLogFilter::class)]
class AuditLogFilterTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $filter = new AuditLogFilter(
            logType: 'audit',
            riskLevel: 'high',
            startTime: '2024-01-01T00:00:00Z',
            endTime: '2024-01-01T23:59:59Z'
        );

        $this->assertEquals('audit', $filter->logType);
        $this->assertEquals('high', $filter->riskLevel);
        $this->assertEquals('2024-01-01T00:00:00Z', $filter->startTime);
        $this->assertEquals('2024-01-01T23:59:59Z', $filter->endTime);
    }

    public function testConstructorWithNullParameters(): void
    {
        $filter = new AuditLogFilter();

        $this->assertNull($filter->logType);
        $this->assertNull($filter->riskLevel);
        $this->assertNull($filter->startTime);
        $this->assertNull($filter->endTime);
    }

    public function testConstructorWithPartialParameters(): void
    {
        $filter = new AuditLogFilter(
            logType: 'security',
            riskLevel: null,
            startTime: '2024-01-01T00:00:00Z',
            endTime: null
        );

        $this->assertEquals('security', $filter->logType);
        $this->assertNull($filter->riskLevel);
        $this->assertEquals('2024-01-01T00:00:00Z', $filter->startTime);
        $this->assertNull($filter->endTime);
    }

    public function testToArrayWithAllParameters(): void
    {
        $filter = new AuditLogFilter(
            logType: 'audit',
            riskLevel: 'medium',
            startTime: '2024-01-01T00:00:00Z',
            endTime: '2024-01-02T00:00:00Z'
        );

        $expected = [
            'LogType' => 'audit',
            'RiskLevel' => 'medium',
            'StartTime' => '2024-01-01T00:00:00Z',
            'EndTime' => '2024-01-02T00:00:00Z',
        ];

        $this->assertEquals($expected, $filter->toArray());
    }

    public function testToArrayWithNullParameters(): void
    {
        $filter = new AuditLogFilter();

        $this->assertEquals([], $filter->toArray());
    }

    public function testToArrayWithPartialParameters(): void
    {
        $filter = new AuditLogFilter(
            logType: 'security',
            riskLevel: null,
            startTime: '2024-01-01T00:00:00Z',
            endTime: null
        );

        $expected = [
            'LogType' => 'security',
            'StartTime' => '2024-01-01T00:00:00Z',
        ];

        $this->assertEquals($expected, $filter->toArray());
    }

    public function testToArrayWithOnlyLogType(): void
    {
        $filter = new AuditLogFilter(logType: 'performance');

        $expected = [
            'LogType' => 'performance',
        ];

        $this->assertEquals($expected, $filter->toArray());
    }

    public function testToArrayWithOnlyRiskLevel(): void
    {
        $filter = new AuditLogFilter(riskLevel: 'low');

        $expected = [
            'RiskLevel' => 'low',
        ];

        $this->assertEquals($expected, $filter->toArray());
    }

    public function testToArrayWithOnlyTimeRange(): void
    {
        $filter = new AuditLogFilter(
            startTime: '2024-01-01T00:00:00Z',
            endTime: '2024-01-01T23:59:59Z'
        );

        $expected = [
            'StartTime' => '2024-01-01T00:00:00Z',
            'EndTime' => '2024-01-01T23:59:59Z',
        ];

        $this->assertEquals($expected, $filter->toArray());
    }
}
