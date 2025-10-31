<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\VolcanoArkApiBundle\DTO\AuditLogItem;
use Tourze\VolcanoArkApiBundle\DTO\AuditLogResult;

/**
 * @internal
 */
#[CoversClass(AuditLogResult::class)]
class AuditLogResultTest extends TestCase
{
    public function testConstructor(): void
    {
        $item1 = new AuditLogItem(
            resourceId: 'resource-1',
            resourceType: 'api_key',
            logType: 'audit',
            logDetail: 'First log',
            logContents: [['key1' => 'value1']],
            riskLevel: 'low',
            timestamp: '2024-01-01T10:00:00Z'
        );

        $item2 = new AuditLogItem(
            resourceId: 'resource-2',
            resourceType: 'model',
            logType: 'security',
            logDetail: 'Second log',
            logContents: [['key2' => 'value2']],
            riskLevel: 'high',
            timestamp: '2024-01-01T11:00:00Z'
        );

        $result = new AuditLogResult(
            totalCount: 50,
            pageNumber: 2,
            pageSize: 20,
            items: [$item1, $item2]
        );

        $this->assertEquals(50, $result->totalCount);
        $this->assertEquals(2, $result->pageNumber);
        $this->assertEquals(20, $result->pageSize);
        $this->assertCount(2, $result->items);
        $this->assertEquals($item1, $result->items[0]);
        $this->assertEquals($item2, $result->items[1]);
    }

    public function testConstructorWithEmptyItems(): void
    {
        $result = new AuditLogResult(
            totalCount: 0,
            pageNumber: 1,
            pageSize: 10,
            items: []
        );

        $this->assertEquals(0, $result->totalCount);
        $this->assertEquals(1, $result->pageNumber);
        $this->assertEquals(10, $result->pageSize);
        $this->assertCount(0, $result->items);
        $this->assertEquals([], $result->items);
    }

    public function testFromArrayWithCompleteData(): void
    {
        $data = [
            'TotalCount' => 100,
            'PageNumber' => 3,
            'PageSize' => 25,
            'Items' => [
                [
                    'ResourceId' => 'resource-123',
                    'ResourceType' => 'endpoint',
                    'LogType' => 'access',
                    'LogDetail' => 'API access log',
                    'LogContents' => [['method' => 'GET']],
                    'RiskLevel' => 'medium',
                    'Timestamp' => '2024-01-02T12:30:00Z',
                ],
                [
                    'ResourceId' => 'resource-456',
                    'ResourceType' => 'user',
                    'LogType' => 'login',
                    'LogDetail' => 'User login',
                    'LogContents' => [['ip' => '192.168.1.1']],
                    'RiskLevel' => 'low',
                    'Timestamp' => '2024-01-02T13:00:00Z',
                ],
            ],
        ];

        $result = AuditLogResult::fromArray($data);

        $this->assertEquals(100, $result->totalCount);
        $this->assertEquals(3, $result->pageNumber);
        $this->assertEquals(25, $result->pageSize);
        $this->assertCount(2, $result->items);

        $firstItem = $result->items[0];
        $this->assertEquals('resource-123', $firstItem->resourceId);
        $this->assertEquals('endpoint', $firstItem->resourceType);
        $this->assertEquals('access', $firstItem->logType);
        $this->assertEquals('API access log', $firstItem->logDetail);
        $this->assertEquals([['method' => 'GET']], $firstItem->logContents);
        $this->assertEquals('medium', $firstItem->riskLevel);
        $this->assertEquals('2024-01-02T12:30:00Z', $firstItem->timestamp);

        $secondItem = $result->items[1];
        $this->assertEquals('resource-456', $secondItem->resourceId);
        $this->assertEquals('user', $secondItem->resourceType);
        $this->assertEquals('login', $secondItem->logType);
    }

    public function testFromArrayWithMissingData(): void
    {
        $data = [
            'TotalCount' => 5,
            // Missing PageNumber, PageSize, Items
        ];

        $result = AuditLogResult::fromArray($data);

        $this->assertEquals(5, $result->totalCount);
        $this->assertEquals(1, $result->pageNumber);
        $this->assertEquals(10, $result->pageSize);
        $this->assertCount(0, $result->items);
    }

    public function testFromArrayWithEmptyArray(): void
    {
        $result = AuditLogResult::fromArray([]);

        $this->assertEquals(0, $result->totalCount);
        $this->assertEquals(1, $result->pageNumber);
        $this->assertEquals(10, $result->pageSize);
        $this->assertCount(0, $result->items);
    }

    public function testFromArrayWithEmptyItems(): void
    {
        $data = [
            'TotalCount' => 0,
            'PageNumber' => 1,
            'PageSize' => 15,
            'Items' => [],
        ];

        $result = AuditLogResult::fromArray($data);

        $this->assertEquals(0, $result->totalCount);
        $this->assertEquals(1, $result->pageNumber);
        $this->assertEquals(15, $result->pageSize);
        $this->assertCount(0, $result->items);
    }

    public function testFromArrayWithPartialItemData(): void
    {
        $data = [
            'TotalCount' => 1,
            'PageNumber' => 1,
            'PageSize' => 10,
            'Items' => [
                [
                    'ResourceId' => 'resource-partial',
                    'LogType' => 'audit',
                    // Missing other fields
                ],
            ],
        ];

        $result = AuditLogResult::fromArray($data);

        $this->assertEquals(1, $result->totalCount);
        $this->assertCount(1, $result->items);

        $item = $result->items[0];
        $this->assertEquals('resource-partial', $item->resourceId);
        $this->assertEquals('', $item->resourceType);
        $this->assertEquals('audit', $item->logType);
        $this->assertEquals('', $item->logDetail);
        $this->assertEquals([], $item->logContents);
        $this->assertEquals('', $item->riskLevel);
        $this->assertEquals('', $item->timestamp);
    }

    public function testFromArrayWithLargeDataSet(): void
    {
        $items = [];
        for ($i = 1; $i <= 100; ++$i) {
            $items[] = [
                'ResourceId' => "resource-{$i}",
                'ResourceType' => 'generated',
                'LogType' => 'test',
                'LogDetail' => "Generated log {$i}",
                'LogContents' => [['index' => (string) $i]],
                'RiskLevel' => 'low',
                'Timestamp' => "2024-01-01T{$i}:00:00Z",
            ];
        }

        $data = [
            'TotalCount' => 1000,
            'PageNumber' => 1,
            'PageSize' => 100,
            'Items' => $items,
        ];

        $result = AuditLogResult::fromArray($data);

        $this->assertEquals(1000, $result->totalCount);
        $this->assertEquals(1, $result->pageNumber);
        $this->assertEquals(100, $result->pageSize);
        $this->assertCount(100, $result->items);

        $firstItem = $result->items[0];
        $this->assertEquals('resource-1', $firstItem->resourceId);

        $lastItem = $result->items[99];
        $this->assertEquals('resource-100', $lastItem->resourceId);
    }
}
