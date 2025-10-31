<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\VolcanoArkApiBundle\DTO\UsageMetricValue;

/**
 * @internal
 */
#[CoversClass(UsageMetricValue::class)]
class UsageMetricValueTest extends TestCase
{
    public function testConstructor(): void
    {
        $value = new UsageMetricValue(1640995200, 150.5);

        $this->assertEquals(1640995200, $value->timestamp);
        $this->assertEquals(150.5, $value->value);
    }

    public function testConstructorWithIntegerValue(): void
    {
        $value = new UsageMetricValue(1640995260, 100.0);

        $this->assertEquals(1640995260, $value->timestamp);
        $this->assertEquals(100.0, $value->value);
    }

    public function testConstructorWithZeroValues(): void
    {
        $value = new UsageMetricValue(0, 0.0);

        $this->assertEquals(0, $value->timestamp);
        $this->assertEquals(0.0, $value->value);
    }

    public function testConstructorWithNegativeTimestamp(): void
    {
        $value = new UsageMetricValue(-1640995200, 50.0);

        $this->assertEquals(-1640995200, $value->timestamp);
        $this->assertEquals(50.0, $value->value);
    }

    public function testConstructorWithNegativeValue(): void
    {
        $value = new UsageMetricValue(1640995200, -25.5);

        $this->assertEquals(1640995200, $value->timestamp);
        $this->assertEquals(-25.5, $value->value);
    }

    public function testFromArrayWithCompleteData(): void
    {
        $data = [
            'Timestamp' => 1640995200,
            'Value' => 200.75,
        ];

        $value = UsageMetricValue::fromArray($data);

        $this->assertEquals(1640995200, $value->timestamp);
        $this->assertEquals(200.75, $value->value);
    }

    public function testFromArrayWithMissingTimestamp(): void
    {
        $data = [
            'Value' => 100.0,
        ];

        $value = UsageMetricValue::fromArray($data);

        $this->assertEquals(0, $value->timestamp);
        $this->assertEquals(100.0, $value->value);
    }

    public function testFromArrayWithMissingValue(): void
    {
        $data = [
            'Timestamp' => 1640995200,
        ];

        $value = UsageMetricValue::fromArray($data);

        $this->assertEquals(1640995200, $value->timestamp);
        $this->assertEquals(0.0, $value->value);
    }

    public function testFromArrayWithEmptyArray(): void
    {
        $value = UsageMetricValue::fromArray([]);

        $this->assertEquals(0, $value->timestamp);
        $this->assertEquals(0.0, $value->value);
    }

    public function testFromArrayWithStringValue(): void
    {
        $data = [
            'Timestamp' => 1640995200,
            'Value' => '150.25',
        ];

        $value = UsageMetricValue::fromArray($data);

        $this->assertEquals(1640995200, $value->timestamp);
        $this->assertEquals(150.25, $value->value);
    }

    public function testFromArrayWithIntegerValue(): void
    {
        $data = [
            'Timestamp' => 1640995200,
            'Value' => 100,
        ];

        $value = UsageMetricValue::fromArray($data);

        $this->assertEquals(1640995200, $value->timestamp);
        $this->assertEquals(100.0, $value->value);
    }

    public function testFromArrayWithStringTimestamp(): void
    {
        $data = [
            'Timestamp' => '1640995200',
            'Value' => 75.5,
        ];

        $value = UsageMetricValue::fromArray($data);

        $this->assertEquals(1640995200, $value->timestamp);
        $this->assertEquals(75.5, $value->value);
    }

    public function testToArray(): void
    {
        $value = new UsageMetricValue(1640995200, 125.75);

        $expected = [
            'Timestamp' => 1640995200,
            'Value' => 125.75,
        ];

        $this->assertEquals($expected, $value->toArray());
    }

    public function testToArrayWithZeroValues(): void
    {
        $value = new UsageMetricValue(0, 0.0);

        $expected = [
            'Timestamp' => 0,
            'Value' => 0.0,
        ];

        $this->assertEquals($expected, $value->toArray());
    }

    public function testToArrayWithNegativeValues(): void
    {
        $value = new UsageMetricValue(-1640995200, -50.25);

        $expected = [
            'Timestamp' => -1640995200,
            'Value' => -50.25,
        ];

        $this->assertEquals($expected, $value->toArray());
    }

    public function testRoundTripConversion(): void
    {
        $originalData = [
            'Timestamp' => 1640995200,
            'Value' => 99.99,
        ];

        $value = UsageMetricValue::fromArray($originalData);
        $convertedData = $value->toArray();

        $this->assertEquals($originalData, $convertedData);
    }

    public function testRoundTripConversionWithStringInput(): void
    {
        $originalData = [
            'Timestamp' => '1640995200',
            'Value' => '123.45',
        ];

        $expectedData = [
            'Timestamp' => 1640995200,
            'Value' => 123.45,
        ];

        $value = UsageMetricValue::fromArray($originalData);
        $convertedData = $value->toArray();

        $this->assertEquals($expectedData, $convertedData);
    }

    public function testWithLargeTimestamp(): void
    {
        $largeTimestamp = 9999999999; // Year 2286
        $value = new UsageMetricValue($largeTimestamp, 1000.0);

        $this->assertEquals($largeTimestamp, $value->timestamp);
        $this->assertEquals(1000.0, $value->value);

        $array = $value->toArray();
        $this->assertEquals($largeTimestamp, $array['Timestamp']);
        $this->assertEquals(1000.0, $array['Value']);
    }

    public function testWithVerySmallValue(): void
    {
        $smallValue = 0.000001;
        $value = new UsageMetricValue(1640995200, $smallValue);

        $this->assertEquals(1640995200, $value->timestamp);
        $this->assertEquals($smallValue, $value->value);

        $array = $value->toArray();
        $this->assertEquals($smallValue, $array['Value']);
    }
}
