<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Entity\ApiKeyUsage;

/**
 * @internal
 */
#[CoversClass(ApiKeyUsage::class)]
class ApiKeyUsageTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new ApiKeyUsage();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'promptTokens' => ['promptTokens', 123],
            'completionTokens' => ['completionTokens', 123],
            'totalTokens' => ['totalTokens', 123],
            'requestCount' => ['requestCount', 123],
            'estimatedCost' => ['estimatedCost', 'test_value'],
        ];
    }

    private ApiKeyUsage $apiKeyUsage;

    private ApiKey $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiKey = new ApiKey();
        $this->apiKey->setName('Test API Key');
        $this->apiKey->setApiKey('test-api-key');
        $this->apiKey->setSecretKey('test-secret-key');

        $this->apiKeyUsage = new ApiKeyUsage();
        $this->apiKeyUsage->setApiKey($this->apiKey);
        $this->apiKeyUsage->setUsageHour(new \DateTimeImmutable('2024-01-01 10:00:00'));
    }

    public function testStringable(): void
    {
        $this->assertInstanceOf(\Stringable::class, $this->apiKeyUsage);

        $stringRepresentation = (string) $this->apiKeyUsage;
        $this->assertStringContainsString('ApiKeyUsage', $stringRepresentation);
        $this->assertStringContainsString('2024-01-01 10:00:00', $stringRepresentation);
    }

    public function testInitialValues(): void
    {
        $this->assertNull($this->apiKeyUsage->getId());
        $this->assertSame($this->apiKey, $this->apiKeyUsage->getApiKey());
        $this->assertEquals(new \DateTimeImmutable('2024-01-01 10:00:00'), $this->apiKeyUsage->getUsageHour());
        $this->assertNull($this->apiKeyUsage->getEndpointId());
        $this->assertNull($this->apiKeyUsage->getBatchJobId());
        $this->assertSame(0, $this->apiKeyUsage->getPromptTokens());
        $this->assertSame(0, $this->apiKeyUsage->getCompletionTokens());
        $this->assertSame(0, $this->apiKeyUsage->getTotalTokens());
        $this->assertSame(0, $this->apiKeyUsage->getRequestCount());
        $this->assertSame('0.0000', $this->apiKeyUsage->getEstimatedCost());
        $this->assertNull($this->apiKeyUsage->getMetadata());
        // TimestampableAware trait的字段默认为null，在持久化时自动设置
        $this->assertNull($this->apiKeyUsage->getCreateTime());
        $this->assertNull($this->apiKeyUsage->getUpdateTime());
    }

    public function testSetAndGetEndpointId(): void
    {
        $endpointId = 'test-endpoint';
        $this->apiKeyUsage->setEndpointId($endpointId);
        $this->assertSame($endpointId, $this->apiKeyUsage->getEndpointId());
    }

    public function testSetAndGetBatchJobId(): void
    {
        $batchJobId = 'batch-job-123';
        $this->apiKeyUsage->setBatchJobId($batchJobId);
        $this->assertSame($batchJobId, $this->apiKeyUsage->getBatchJobId());
    }

    public function testSetAndGetPromptTokens(): void
    {
        $promptTokens = 100;
        $this->apiKeyUsage->setPromptTokens($promptTokens);
        $this->assertSame($promptTokens, $this->apiKeyUsage->getPromptTokens());
        $this->assertSame($promptTokens, $this->apiKeyUsage->getTotalTokens());
    }

    public function testSetAndGetCompletionTokens(): void
    {
        $completionTokens = 50;
        $this->apiKeyUsage->setCompletionTokens($completionTokens);
        $this->assertSame($completionTokens, $this->apiKeyUsage->getCompletionTokens());
        $this->assertSame($completionTokens, $this->apiKeyUsage->getTotalTokens());
    }

    public function testTotalTokensCalculation(): void
    {
        $this->apiKeyUsage->setPromptTokens(100);
        $this->apiKeyUsage->setCompletionTokens(50);
        $this->assertSame(150, $this->apiKeyUsage->getTotalTokens());
    }

    public function testSetAndGetRequestCount(): void
    {
        $requestCount = 5;
        $this->apiKeyUsage->setRequestCount($requestCount);
        $this->assertSame($requestCount, $this->apiKeyUsage->getRequestCount());
    }

    public function testSetAndGetEstimatedCost(): void
    {
        $estimatedCost = '0.0250';
        $this->apiKeyUsage->setEstimatedCost($estimatedCost);
        $this->assertSame($estimatedCost, $this->apiKeyUsage->getEstimatedCost());
    }

    public function testSetAndGetMetadata(): void
    {
        $metadata = ['key' => 'value', 'test' => true];
        $this->apiKeyUsage->setMetadata($metadata);
        $this->assertSame($metadata, $this->apiKeyUsage->getMetadata());
    }

    public function testAddUsage(): void
    {
        $this->apiKeyUsage->setPromptTokens(100);
        $this->apiKeyUsage->setCompletionTokens(50);
        $this->apiKeyUsage->setRequestCount(1);

        $this->apiKeyUsage->addUsage(50, 25);

        $this->assertSame(150, $this->apiKeyUsage->getPromptTokens());
        $this->assertSame(75, $this->apiKeyUsage->getCompletionTokens());
        $this->assertSame(225, $this->apiKeyUsage->getTotalTokens());
        $this->assertSame(2, $this->apiKeyUsage->getRequestCount());
    }

    public function testIncrementRequestCount(): void
    {
        $this->apiKeyUsage->setRequestCount(1);
        $this->apiKeyUsage->incrementRequestCount();
        $this->assertSame(2, $this->apiKeyUsage->getRequestCount());
    }

    public function testEntityAnnotations(): void
    {
        $reflectionClass = new \ReflectionClass($this->apiKeyUsage);

        // 检查实体注解
        $this->assertTrue($reflectionClass->hasMethod('getId'));
        $this->assertTrue($reflectionClass->hasMethod('getApiKey'));
        $this->assertTrue($reflectionClass->hasMethod('getUsageHour'));
        $this->assertTrue($reflectionClass->hasMethod('getCreateTime'));
        $this->assertTrue($reflectionClass->hasMethod('getUpdateTime'));
    }

    public function testValidationConstraints(): void
    {
        $reflectionClass = new \ReflectionClass($this->apiKeyUsage);

        // 检查是否有验证约束注解
        $properties = $reflectionClass->getProperties();

        $usageHourProperty = null;
        foreach ($properties as $property) {
            if ('usageHour' === $property->getName()) {
                $usageHourProperty = $property;
                break;
            }
        }

        $this->assertNotNull($usageHourProperty);

        // 检查属性类型
        $this->assertTrue($usageHourProperty->hasType());
        $type = $usageHourProperty->getType();
        $this->assertNotNull($type);

        // 安全地获取类型名称
        $typeName = method_exists($type, 'getName') ? $type->getName() : $type->__toString();
        $this->assertEquals(\DateTimeImmutable::class, $typeName);
    }
}
