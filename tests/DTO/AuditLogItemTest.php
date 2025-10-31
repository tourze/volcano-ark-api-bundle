<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\VolcanoArkApiBundle\DTO\AuditLogItem;

/**
 * @internal
 */
#[CoversClass(AuditLogItem::class)]
class AuditLogItemTest extends TestCase
{
    public function testConstructor(): void
    {
        $logContents = [
            ['key1' => 'value1'],
            ['key2' => 'value2'],
        ];

        $item = new AuditLogItem(
            resourceId: 'resource-123',
            resourceType: 'api_key',
            logType: 'audit',
            logDetail: 'API key accessed',
            logContents: $logContents,
            riskLevel: 'medium',
            timestamp: '2024-01-01T12:00:00Z'
        );

        $this->assertEquals('resource-123', $item->resourceId);
        $this->assertEquals('api_key', $item->resourceType);
        $this->assertEquals('audit', $item->logType);
        $this->assertEquals('API key accessed', $item->logDetail);
        $this->assertEquals($logContents, $item->logContents);
        $this->assertEquals('medium', $item->riskLevel);
        $this->assertEquals('2024-01-01T12:00:00Z', $item->timestamp);
    }

    public function testFromArrayWithCompleteData(): void
    {
        $data = [
            'ResourceId' => 'resource-456',
            'ResourceType' => 'model',
            'LogType' => 'security',
            'LogDetail' => 'Model accessed by unauthorized user',
            'LogContents' => [
                ['user' => 'test@example.com'],
                ['action' => 'unauthorized_access'],
            ],
            'RiskLevel' => 'high',
            'Timestamp' => '2024-01-02T14:30:00Z',
        ];

        $item = AuditLogItem::fromArray($data);

        $this->assertEquals('resource-456', $item->resourceId);
        $this->assertEquals('model', $item->resourceType);
        $this->assertEquals('security', $item->logType);
        $this->assertEquals('Model accessed by unauthorized user', $item->logDetail);
        $this->assertEquals($data['LogContents'], $item->logContents);
        $this->assertEquals('high', $item->riskLevel);
        $this->assertEquals('2024-01-02T14:30:00Z', $item->timestamp);
    }

    public function testFromArrayWithMissingData(): void
    {
        $data = [
            'ResourceId' => 'resource-789',
            'LogType' => 'performance',
            // Missing other fields
        ];

        $item = AuditLogItem::fromArray($data);

        $this->assertEquals('resource-789', $item->resourceId);
        $this->assertEquals('', $item->resourceType);
        $this->assertEquals('performance', $item->logType);
        $this->assertEquals('', $item->logDetail);
        $this->assertEquals([], $item->logContents);
        $this->assertEquals('', $item->riskLevel);
        $this->assertEquals('', $item->timestamp);
    }

    public function testFromArrayWithEmptyArray(): void
    {
        $item = AuditLogItem::fromArray([]);

        $this->assertEquals('', $item->resourceId);
        $this->assertEquals('', $item->resourceType);
        $this->assertEquals('', $item->logType);
        $this->assertEquals('', $item->logDetail);
        $this->assertEquals([], $item->logContents);
        $this->assertEquals('', $item->riskLevel);
        $this->assertEquals('', $item->timestamp);
    }

    public function testToArray(): void
    {
        $logContents = [
            ['method' => 'POST'],
            ['endpoint' => '/api/chat/completions'],
        ];

        $item = new AuditLogItem(
            resourceId: 'resource-abc',
            resourceType: 'endpoint',
            logType: 'access',
            logDetail: 'API endpoint called',
            logContents: $logContents,
            riskLevel: 'low',
            timestamp: '2024-01-03T09:15:30Z'
        );

        $expected = [
            'ResourceId' => 'resource-abc',
            'ResourceType' => 'endpoint',
            'LogType' => 'access',
            'LogDetail' => 'API endpoint called',
            'LogContents' => $logContents,
            'RiskLevel' => 'low',
            'Timestamp' => '2024-01-03T09:15:30Z',
        ];

        $this->assertEquals($expected, $item->toArray());
    }

    public function testToArrayWithEmptyLogContents(): void
    {
        $item = new AuditLogItem(
            resourceId: 'resource-def',
            resourceType: 'user',
            logType: 'login',
            logDetail: 'User login attempt',
            logContents: [],
            riskLevel: 'medium',
            timestamp: '2024-01-04T16:45:00Z'
        );

        $expected = [
            'ResourceId' => 'resource-def',
            'ResourceType' => 'user',
            'LogType' => 'login',
            'LogDetail' => 'User login attempt',
            'LogContents' => [],
            'RiskLevel' => 'medium',
            'Timestamp' => '2024-01-04T16:45:00Z',
        ];

        $this->assertEquals($expected, $item->toArray());
    }

    public function testRoundTripConversion(): void
    {
        $originalData = [
            'ResourceId' => 'resource-xyz',
            'ResourceType' => 'database',
            'LogType' => 'query',
            'LogDetail' => 'Database query executed',
            'LogContents' => [
                ['query' => 'SELECT * FROM users'],
                ['duration' => '0.05s'],
            ],
            'RiskLevel' => 'low',
            'Timestamp' => '2024-01-05T20:00:00Z',
        ];

        $item = AuditLogItem::fromArray($originalData);
        $convertedData = $item->toArray();

        $this->assertEquals($originalData, $convertedData);
    }
}
