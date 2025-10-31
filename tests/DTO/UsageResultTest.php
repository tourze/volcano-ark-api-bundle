<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\VolcanoArkApiBundle\DTO\UsageMetricItem;
use Tourze\VolcanoArkApiBundle\DTO\UsageMetricValue;
use Tourze\VolcanoArkApiBundle\DTO\UsageResult;

/**
 * @internal
 */
#[CoversClass(UsageResult::class)]
class UsageResultTest extends TestCase
{
    public function testConstructor(): void
    {
        $value1 = new UsageMetricValue(1640995200, 100.0);
        $value2 = new UsageMetricValue(1640995260, 200.0);

        $item1 = new UsageMetricItem(
            [['Key' => 'EndpointId', 'Value' => 'endpoint-123']],
            [$value1]
        );

        $item2 = new UsageMetricItem(
            [['Key' => 'ModelId', 'Value' => 'ep-20240101-test123']],
            [$value2]
        );

        $result = new UsageResult('PromptTokens', [$item1, $item2]);

        $this->assertEquals('PromptTokens', $result->name);
        $this->assertCount(2, $result->metricItems);
        $this->assertEquals($item1, $result->metricItems[0]);
        $this->assertEquals($item2, $result->metricItems[1]);
    }

    public function testConstructorWithEmptyMetricItems(): void
    {
        $result = new UsageResult('CompletionTokens', []);

        $this->assertEquals('CompletionTokens', $result->name);
        $this->assertCount(0, $result->metricItems);
        $this->assertEquals([], $result->metricItems);
    }

    public function testConstructorWithEmptyName(): void
    {
        $value = new UsageMetricValue(1640995200, 50.0);
        $item = new UsageMetricItem(
            [['Key' => 'TestKey', 'Value' => 'TestValue']],
            [$value]
        );

        $result = new UsageResult('', [$item]);

        $this->assertEquals('', $result->name);
        $this->assertCount(1, $result->metricItems);
    }

    public function testFromArrayWithCompleteData(): void
    {
        $data = [
            'Name' => 'TotalTokens',
            'MetricItems' => [
                [
                    'Tags' => [
                        ['Key' => 'EndpointId', 'Value' => 'endpoint-456'],
                        ['Key' => 'ModelId', 'Value' => 'claude-3'],
                    ],
                    'Values' => [
                        ['Timestamp' => 1640995200, 'Value' => 150.0],
                        ['Timestamp' => 1640995260, 'Value' => 175.0],
                    ],
                ],
                [
                    'Tags' => [
                        ['Key' => 'Region', 'Value' => 'us-east-1'],
                    ],
                    'Values' => [
                        ['Timestamp' => 1640995320, 'Value' => 300.0],
                    ],
                ],
            ],
        ];

        $result = UsageResult::fromArray($data);

        $this->assertEquals('TotalTokens', $result->name);
        $this->assertCount(2, $result->metricItems);

        $firstItem = $result->metricItems[0];
        $this->assertCount(2, $firstItem->tags);
        $this->assertEquals('EndpointId', $firstItem->tags[0]['Key']);
        $this->assertEquals('endpoint-456', $firstItem->tags[0]['Value']);
        $this->assertCount(2, $firstItem->values);
        $this->assertEquals(1640995200, $firstItem->values[0]->timestamp);
        $this->assertEquals(150.0, $firstItem->values[0]->value);

        $secondItem = $result->metricItems[1];
        $this->assertCount(1, $secondItem->tags);
        $this->assertCount(1, $secondItem->values);
        $this->assertEquals(300.0, $secondItem->values[0]->value);
    }

    public function testFromArrayWithMissingData(): void
    {
        $data = [
            'Name' => 'IncompleteResult',
            // Missing MetricItems
        ];

        $result = UsageResult::fromArray($data);

        $this->assertEquals('IncompleteResult', $result->name);
        $this->assertCount(0, $result->metricItems);
    }

    public function testFromArrayWithEmptyArray(): void
    {
        $result = UsageResult::fromArray([]);

        $this->assertEquals('', $result->name);
        $this->assertCount(0, $result->metricItems);
    }

    public function testFromArrayWithEmptyMetricItems(): void
    {
        $data = [
            'Name' => 'EmptyMetrics',
            'MetricItems' => [],
        ];

        $result = UsageResult::fromArray($data);

        $this->assertEquals('EmptyMetrics', $result->name);
        $this->assertCount(0, $result->metricItems);
    }

    public function testFromArrayWithMissingName(): void
    {
        $data = [
            'MetricItems' => [
                [
                    'Tags' => [['Key' => 'TestKey', 'Value' => 'TestValue']],
                    'Values' => [['Timestamp' => 1640995200, 'Value' => 100.0]],
                ],
            ],
        ];

        $result = UsageResult::fromArray($data);

        $this->assertEquals('', $result->name);
        $this->assertCount(1, $result->metricItems);
    }

    public function testToArray(): void
    {
        $value1 = new UsageMetricValue(1640995200, 50.0);
        $value2 = new UsageMetricValue(1640995260, 75.0);

        $item1 = new UsageMetricItem(
            [['Key' => 'Service', 'Value' => 'volcano-ark']],
            [$value1]
        );

        $item2 = new UsageMetricItem(
            [['Key' => 'Environment', 'Value' => 'production']],
            [$value2]
        );

        $result = new UsageResult('RequestCount', [$item1, $item2]);

        $expected = [
            'Name' => 'RequestCount',
            'MetricItems' => [
                [
                    'Tags' => [['Key' => 'Service', 'Value' => 'volcano-ark']],
                    'Values' => [['Timestamp' => 1640995200, 'Value' => 50.0]],
                ],
                [
                    'Tags' => [['Key' => 'Environment', 'Value' => 'production']],
                    'Values' => [['Timestamp' => 1640995260, 'Value' => 75.0]],
                ],
            ],
        ];

        $this->assertEquals($expected, $result->toArray());
    }

    public function testToArrayWithEmptyMetricItems(): void
    {
        $result = new UsageResult('EmptyResult', []);

        $expected = [
            'Name' => 'EmptyResult',
            'MetricItems' => [],
        ];

        $this->assertEquals($expected, $result->toArray());
    }

    public function testToArrayWithEmptyName(): void
    {
        $value = new UsageMetricValue(1640995200, 25.0);
        $item = new UsageMetricItem(
            [['Key' => 'OnlyKey', 'Value' => 'OnlyValue']],
            [$value]
        );

        $result = new UsageResult('', [$item]);

        $expected = [
            'Name' => '',
            'MetricItems' => [
                [
                    'Tags' => [['Key' => 'OnlyKey', 'Value' => 'OnlyValue']],
                    'Values' => [['Timestamp' => 1640995200, 'Value' => 25.0]],
                ],
            ],
        ];

        $this->assertEquals($expected, $result->toArray());
    }

    public function testRoundTripConversion(): void
    {
        $originalData = [
            'Name' => 'RoundTripTest',
            'MetricItems' => [
                [
                    'Tags' => [
                        ['Key' => 'MetricType', 'Value' => 'TokenCount'],
                        ['Key' => 'ApiVersion', 'Value' => 'v1'],
                    ],
                    'Values' => [
                        ['Timestamp' => 1640995200, 'Value' => 100.0],
                        ['Timestamp' => 1640995260, 'Value' => 200.0],
                    ],
                ],
            ],
        ];

        $result = UsageResult::fromArray($originalData);
        $convertedData = $result->toArray();

        $this->assertEquals($originalData, $convertedData);
    }

    public function testWithSingleMetricItem(): void
    {
        $data = [
            'Name' => 'SingleMetric',
            'MetricItems' => [
                [
                    'Tags' => [['Key' => 'OnlyTag', 'Value' => 'OnlyValue']],
                    'Values' => [['Timestamp' => 1640995200, 'Value' => 42.0]],
                ],
            ],
        ];

        $result = UsageResult::fromArray($data);

        $this->assertEquals('SingleMetric', $result->name);
        $this->assertCount(1, $result->metricItems);

        $item = $result->metricItems[0];
        $this->assertCount(1, $item->tags);
        $this->assertCount(1, $item->values);
        $this->assertEquals('OnlyTag', $item->tags[0]['Key']);
        $this->assertEquals(42.0, $item->values[0]->value);
    }

    public function testWithMultipleValues(): void
    {
        $data = [
            'Name' => 'MultiValueMetric',
            'MetricItems' => [
                [
                    'Tags' => [['Key' => 'TestKey', 'Value' => 'TestValue']],
                    'Values' => [
                        ['Timestamp' => 1640995200, 'Value' => 10.0],
                        ['Timestamp' => 1640995260, 'Value' => 20.0],
                        ['Timestamp' => 1640995320, 'Value' => 30.0],
                        ['Timestamp' => 1640995380, 'Value' => 40.0],
                    ],
                ],
            ],
        ];

        $result = UsageResult::fromArray($data);

        $this->assertEquals('MultiValueMetric', $result->name);
        $this->assertCount(1, $result->metricItems);

        $item = $result->metricItems[0];
        $this->assertCount(4, $item->values);
        $this->assertEquals(10.0, $item->values[0]->value);
        $this->assertEquals(20.0, $item->values[1]->value);
        $this->assertEquals(30.0, $item->values[2]->value);
        $this->assertEquals(40.0, $item->values[3]->value);
    }
}
