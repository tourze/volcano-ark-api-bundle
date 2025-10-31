<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Entity\ApiKeyUsage;
use Tourze\VolcanoArkApiBundle\EventListener\TimestampableListener;

/**
 * @internal
 */
#[CoversClass(TimestampableListener::class)]
class TimestampableListenerTest extends TestCase
{
    private TimestampableListener $listener;

    private PreUpdateEventArgs&MockObject $eventArgs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new TimestampableListener();
        $this->eventArgs = $this->createMock(PreUpdateEventArgs::class);
    }

    public function testPreUpdateWithApiKeyEntity(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setName('Test Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setSecretKey('test-secret-key');
        $apiKey->setRegion('cn-beijing');

        // 确保 updateTime 初始为 null（TimestampableAware trait默认值）
        $this->assertNull($apiKey->getUpdateTime());

        $this->listener->preUpdate($apiKey, $this->eventArgs);

        // 验证 updateTime 被设置
        $this->assertInstanceOf(\DateTimeImmutable::class, $apiKey->getUpdateTime());
        $this->assertLessThanOrEqual(new \DateTimeImmutable(), $apiKey->getUpdateTime());
    }

    public function testPreUpdateWithApiKeyUsageEntity(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setName('Test Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setSecretKey('test-secret-key');
        $apiKey->setRegion('cn-beijing');

        $apiKeyUsage = new ApiKeyUsage();
        $apiKeyUsage->setApiKey($apiKey);
        $apiKeyUsage->setUsageHour(new \DateTimeImmutable());
        $apiKeyUsage->setPromptTokens(100);
        $apiKeyUsage->setCompletionTokens(50);
        $apiKeyUsage->setRequestCount(1);

        // 确保 updateTime 初始为 null（TimestampableAware trait默认值）
        $this->assertNull($apiKeyUsage->getUpdateTime());

        $this->listener->preUpdate($apiKeyUsage, $this->eventArgs);

        // 验证 updateTime 被设置
        $this->assertInstanceOf(\DateTimeImmutable::class, $apiKeyUsage->getUpdateTime());
        $this->assertLessThanOrEqual(new \DateTimeImmutable(), $apiKeyUsage->getUpdateTime());
    }

    public function testPreUpdateWithNonTimestampableEntity(): void
    {
        $nonTimestampableEntity = new \stdClass();

        // 应该不会抛出异常
        $this->listener->preUpdate($nonTimestampableEntity, $this->eventArgs);

        // 由于 stdClass 没有 setUpdateTime 方法，这里只是确保不会出错
        $this->assertInstanceOf(\stdClass::class, $nonTimestampableEntity);
    }

    public function testPreUpdateSetsCurrentTimestamp(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setName('Test Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setSecretKey('test-secret-key');
        $apiKey->setRegion('cn-beijing');

        $beforeUpdate = new \DateTimeImmutable();

        $this->listener->preUpdate($apiKey, $this->eventArgs);

        $afterUpdate = new \DateTimeImmutable();
        $updateTime = $apiKey->getUpdateTime();

        $this->assertInstanceOf(\DateTimeImmutable::class, $updateTime);
        $this->assertGreaterThanOrEqual($beforeUpdate, $updateTime);
        $this->assertLessThanOrEqual($afterUpdate, $updateTime);
    }

    public function testPreUpdatePreservesExistingUpdateTime(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setName('Test Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setSecretKey('test-secret-key');
        $apiKey->setRegion('cn-beijing');

        $existingTimestamp = new \DateTimeImmutable('2024-01-01 10:00:00');
        $apiKey->setUpdateTime($existingTimestamp);

        $this->listener->preUpdate($apiKey, $this->eventArgs);

        // 验证时间戳被更新为新的时间
        $newTimestamp = $apiKey->getUpdateTime();
        $this->assertInstanceOf(\DateTimeImmutable::class, $newTimestamp);
        $this->assertGreaterThan($existingTimestamp, $newTimestamp);
    }

    public function testPreUpdateWithMultipleApiKeyEntities(): void
    {
        $apiKey1 = new ApiKey();
        $apiKey1->setName('Test Key 1');
        $apiKey1->setApiKey('test-key-1');
        $apiKey1->setSecretKey('test-secret-1');
        $apiKey1->setRegion('cn-beijing');

        $apiKey2 = new ApiKey();
        $apiKey2->setName('Test Key 2');
        $apiKey2->setApiKey('test-key-2');
        $apiKey2->setSecretKey('test-secret-2');
        $apiKey2->setRegion('cn-shanghai');

        $this->listener->preUpdate($apiKey1, $this->eventArgs);
        $this->listener->preUpdate($apiKey2, $this->eventArgs);

        $this->assertInstanceOf(\DateTimeImmutable::class, $apiKey1->getUpdateTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $apiKey2->getUpdateTime());

        // 两个时间戳应该非常接近但可能不完全相同
        $timeDiff = abs($apiKey1->getUpdateTime()->getTimestamp() - $apiKey2->getUpdateTime()->getTimestamp());
        $this->assertLessThanOrEqual(1, $timeDiff, 'Timestamps should be within 1 second of each other');
    }

    public function testPreUpdateWithBothEntityTypes(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setName('Test Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setSecretKey('test-secret-key');
        $apiKey->setRegion('cn-beijing');

        $apiKeyUsage = new ApiKeyUsage();
        $apiKeyUsage->setApiKey($apiKey);
        $apiKeyUsage->setUsageHour(new \DateTimeImmutable());
        $apiKeyUsage->setPromptTokens(100);
        $apiKeyUsage->setCompletionTokens(50);
        $apiKeyUsage->setRequestCount(1);

        $this->listener->preUpdate($apiKey, $this->eventArgs);
        $this->listener->preUpdate($apiKeyUsage, $this->eventArgs);

        $this->assertInstanceOf(\DateTimeImmutable::class, $apiKey->getUpdateTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $apiKeyUsage->getUpdateTime());
    }

    public function testPreUpdateTimestampPrecision(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setName('Test Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setSecretKey('test-secret-key');
        $apiKey->setRegion('cn-beijing');

        $this->listener->preUpdate($apiKey, $this->eventArgs);

        $updateTime = $apiKey->getUpdateTime();
        $this->assertInstanceOf(\DateTimeImmutable::class, $updateTime);

        // 验证时间戳格式
        $formattedTime = $updateTime->format('Y-m-d H:i:s');
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $formattedTime);
    }

    public function testPreUpdateImmutability(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setName('Test Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setSecretKey('test-secret-key');
        $apiKey->setRegion('cn-beijing');

        $this->listener->preUpdate($apiKey, $this->eventArgs);

        $firstTimestamp = $apiKey->getUpdateTime();

        // 检查 updateTime 不为 null
        $this->assertNotNull($firstTimestamp, 'UpdateTime should not be null after preUpdate');

        // 由于 DateTimeImmutable 的不变性，即使尝试修改，原对象也不会改变
        $modifiedTimestamp = $firstTimestamp->modify('+1 hour');

        $this->assertEquals($firstTimestamp, $apiKey->getUpdateTime());
        $this->assertNotEquals($modifiedTimestamp, $apiKey->getUpdateTime());
    }
}
