<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\VolcanoArkApiBundle\DTO\UsageMetricItem;
use Tourze\VolcanoArkApiBundle\DTO\UsageMetricValue;

/**
 * @internal
 */
#[CoversClass(UsageMetricItem::class)]
class UsageMetricItemTest extends TestCase
{
    public function testConstructor(): void
    {
        $tags = [
            ['Key' => 'ModelId', 'Value' => 'ep-20240101-test123'],
            ['Key' => 'EndpointId', 'Value' => 'endpoint-test'],
        ];

        $values = [
            new UsageMetricValue(1640995200, 100.5),
            new UsageMetricValue(1640995260, 200.0),
        ];

        $item = new UsageMetricItem($tags, $values);

        $this->assertEquals($tags, $item->tags);
        $this->assertEquals($values, $item->values);
        $this->assertCount(2, $item->tags);
        $this->assertCount(2, $item->values);
    }

    public function testConstructorWithEmptyArrays(): void
    {
        $item = new UsageMetricItem([], []);

        $this->assertEquals([], $item->tags);
        $this->assertEquals([], $item->values);
        $this->assertCount(0, $item->tags);
        $this->assertCount(0, $item->values);
    }

    public function testFromArrayWithCompleteData(): void
    {
        $data = [
            'Tags' => [
                ['Key' => 'ModelId', 'Value' => 'claude-3'],
                ['Key' => 'Region', 'Value' => 'us-east-1'],
            ],
            'Values' => [
                ['Timestamp' => 1640995200, 'Value' => 150.5],
                ['Timestamp' => 1640995260, 'Value' => 75.0],
                ['Timestamp' => 1640995320, 'Value' => 300.25],
            ],
        ];

        $item = UsageMetricItem::fromArray($data);

        $this->assertEquals($data['Tags'], $item->tags);
        $this->assertCount(2, $item->tags);
        $this->assertEquals('ModelId', $item->tags[0]['Key']);
        $this->assertEquals('claude-3', $item->tags[0]['Value']);

        $this->assertCount(3, $item->values);
        $this->assertEquals(1640995200, $item->values[0]->timestamp);
        $this->assertEquals(150.5, $item->values[0]->value);
        $this->assertEquals(1640995260, $item->values[1]->timestamp);
        $this->assertEquals(75.0, $item->values[1]->value);
        $this->assertEquals(1640995320, $item->values[2]->timestamp);
        $this->assertEquals(300.25, $item->values[2]->value);
    }

    public function testFromArrayWithMissingData(): void
    {
        $data = [
            'Tags' => [
                ['Key' => 'OnlyTag', 'Value' => 'test'],
            ],
            // Missing Values
        ];

        $item = UsageMetricItem::fromArray($data);

        $this->assertEquals($data['Tags'], $item->tags);
        $this->assertEquals([], $item->values);
        $this->assertCount(1, $item->tags);
        $this->assertCount(0, $item->values);
    }

    public function testFromArrayWithEmptyArray(): void
    {
        $item = UsageMetricItem::fromArray([]);

        $this->assertEquals([], $item->tags);
        $this->assertEquals([], $item->values);
    }

    public function testFromArrayWithEmptyValues(): void
    {
        $data = [
            'Tags' => [
                ['Key' => 'TestKey', 'Value' => 'TestValue'],
            ],
            'Values' => [],
        ];

        $item = UsageMetricItem::fromArray($data);

        $this->assertEquals($data['Tags'], $item->tags);
        $this->assertEquals([], $item->values);
        $this->assertCount(1, $item->tags);
        $this->assertCount(0, $item->values);
    }

    public function testToArray(): void
    {
        $tags = [
            ['Key' => 'Service', 'Value' => 'volcano-ark'],
            ['Key' => 'Environment', 'Value' => 'production'],
        ];

        $values = [
            new UsageMetricValue(1640995200, 50.0),
            new UsageMetricValue(1640995260, 100.0),
        ];

        $item = new UsageMetricItem($tags, $values);

        $expected = [
            'Tags' => $tags,
            'Values' => [
                ['Timestamp' => 1640995200, 'Value' => 50.0],
                ['Timestamp' => 1640995260, 'Value' => 100.0],
            ],
        ];

        $this->assertEquals($expected, $item->toArray());
    }

    public function testToArrayWithEmptyValues(): void
    {
        $tags = [
            ['Key' => 'OnlyTag', 'Value' => 'OnlyValue'],
        ];

        $item = new UsageMetricItem($tags, []);

        $expected = [
            'Tags' => $tags,
            'Values' => [],
        ];

        $this->assertEquals($expected, $item->toArray());
    }

    public function testToArrayWithEmptyTags(): void
    {
        $values = [
            new UsageMetricValue(1640995200, 25.5),
        ];

        $item = new UsageMetricItem([], $values);

        $expected = [
            'Tags' => [],
            'Values' => [
                ['Timestamp' => 1640995200, 'Value' => 25.5],
            ],
        ];

        $this->assertEquals($expected, $item->toArray());
    }

    public function testRoundTripConversion(): void
    {
        $originalData = [
            'Tags' => [
                ['Key' => 'MetricType', 'Value' => 'TokenUsage'],
                ['Key' => 'ApiKey', 'Value' => 'api-key-123'],
            ],
            'Values' => [
                ['Timestamp' => 1640995200, 'Value' => 100.0],
                ['Timestamp' => 1640995260, 'Value' => 200.5],
            ],
        ];

        $item = UsageMetricItem::fromArray($originalData);
        $convertedData = $item->toArray();

        $this->assertEquals($originalData, $convertedData);
    }

    public function testWithSingleTag(): void
    {
        $data = [
            'Tags' => [
                ['Key' => 'SingleKey', 'Value' => 'SingleValue'],
            ],
            'Values' => [
                ['Timestamp' => 1640995200, 'Value' => 42.0],
            ],
        ];

        $item = UsageMetricItem::fromArray($data);

        $this->assertCount(1, $item->tags);
        $this->assertCount(1, $item->values);
        $this->assertEquals('SingleKey', $item->tags[0]['Key']);
        $this->assertEquals('SingleValue', $item->tags[0]['Value']);
        $this->assertEquals(1640995200, $item->values[0]->timestamp);
        $this->assertEquals(42.0, $item->values[0]->value);
    }
}
