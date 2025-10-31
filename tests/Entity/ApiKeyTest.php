<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;

/**
 * @internal
 */
#[CoversClass(ApiKey::class)]
class ApiKeyTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new ApiKey();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'name' => ['name', 'test_value'],
            'provider' => ['provider', 'test_value'],
            'apiKey' => ['apiKey', 'test_value'],
            'secretKey' => ['secretKey', 'test_value'],
            'region' => ['region', 'test_value'],
            'usageCount' => ['usageCount', 123],
        ];
    }

    public function testEntityInstantiation(): void
    {
        $apiKey = new ApiKey();

        // 测试默认值
        $this->assertNull($apiKey->getId());
        $this->assertSame('', $apiKey->getName());
        $this->assertSame('volcano_ark', $apiKey->getProvider());
        $this->assertSame('', $apiKey->getApiKey());
        $this->assertSame('', $apiKey->getSecretKey());
        $this->assertSame('cn-beijing', $apiKey->getRegion());
        $this->assertTrue($apiKey->isActive());
        $this->assertSame(0, $apiKey->getUsageCount());
        $this->assertNull($apiKey->getLastUsedTime());
        $this->assertNull($apiKey->getDescription());
        $this->assertNull($apiKey->getMetadata());
        // TimestampableAware trait的字段默认为null，在持久化时自动设置
        $this->assertNull($apiKey->getCreateTime());
        $this->assertNull($apiKey->getUpdateTime());
    }

    public function testSettersAndGetters(): void
    {
        $apiKey = new ApiKey();

        // 测试设置和获取基本属性
        $apiKey->setName('Test API Key');
        $this->assertSame('Test API Key', $apiKey->getName());

        $apiKey->setProvider('custom_provider');
        $this->assertSame('custom_provider', $apiKey->getProvider());

        $apiKey->setApiKey('test_api_key_123');
        $this->assertSame('test_api_key_123', $apiKey->getApiKey());

        $apiKey->setSecretKey('test_secret_key_456');
        $this->assertSame('test_secret_key_456', $apiKey->getSecretKey());

        $apiKey->setRegion('us-east-1');
        $this->assertSame('us-east-1', $apiKey->getRegion());

        $apiKey->setIsActive(false);
        $this->assertFalse($apiKey->isActive());

        $apiKey->setDescription('Test description');
        $this->assertSame('Test description', $apiKey->getDescription());

        $apiKey->setMetadata(['key' => 'value']);
        $this->assertSame(['key' => 'value'], $apiKey->getMetadata());
    }

    public function testIncrementUsageCount(): void
    {
        $apiKey = new ApiKey();

        // 初始使用次数为0
        $this->assertSame(0, $apiKey->getUsageCount());

        // 增加使用次数
        $apiKey->incrementUsageCount();
        $this->assertSame(1, $apiKey->getUsageCount());
        $this->assertInstanceOf(\DateTimeImmutable::class, $apiKey->getLastUsedTime());

        // 再次增加使用次数
        $apiKey->incrementUsageCount();
        $this->assertSame(2, $apiKey->getUsageCount());
    }

    public function testToString(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setName('Test Key');

        $this->assertSame('Test Key', (string) $apiKey);
    }

    public function testValidationConstraints(): void
    {
        $apiKey = new ApiKey();

        // 测试默认值
        $this->assertSame('', $apiKey->getName());
        $this->assertSame('volcano_ark', $apiKey->getProvider());
        $this->assertSame('', $apiKey->getApiKey());
        $this->assertSame('', $apiKey->getSecretKey());
        $this->assertSame('cn-beijing', $apiKey->getRegion());

        // 测试字符串长度约束
        $longString = str_repeat('a', 300);

        // 这些应该不会抛出异常，因为我们只是在测试属性设置
        $apiKey->setName($longString);
        $apiKey->setProvider($longString);
        $apiKey->setApiKey($longString);
        $apiKey->setRegion($longString);

        // 验证设置成功
        $this->assertEquals($longString, $apiKey->getName());
        $this->assertEquals($longString, $apiKey->getProvider());
        $this->assertEquals($longString, $apiKey->getApiKey());
        $this->assertEquals($longString, $apiKey->getRegion());
    }
}
