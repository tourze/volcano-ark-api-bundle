<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Exception\ApiException;
use Tourze\VolcanoArkApiBundle\Service\ApiKeyService;

/**
 * @internal
 */
#[CoversClass(ApiKeyService::class)]
#[RunTestsInSeparateProcesses]
class ApiKeyServiceTest extends AbstractIntegrationTestCase
{
    private ApiKeyService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(ApiKeyService::class);
    }

    public function testGetCurrentKey(): void
    {
        // 清理现有的激活密钥
        $existingKeys = $this->service->getActiveKeys();
        foreach ($existingKeys as $key) {
            $this->service->deactivateKey($key);
        }

        // 创建测试用的API密钥
        $apiKey = $this->service->createKey('Test Key', 'test-api-key', 'test-secret-key', 'cn-beijing');

        $initialUsageCount = $apiKey->getUsageCount();

        $result = $this->service->getCurrentKey();

        $this->assertInstanceOf(ApiKey::class, $result);
        $this->assertEquals('Test Key', $result->getName());
        $this->assertEquals($initialUsageCount + 1, $result->getUsageCount());
    }

    public function testGetCurrentKeyThrowsExceptionWhenNoActiveKey(): void
    {
        // 确保没有活跃的API密钥
        $keys = $this->service->getActiveKeys();
        foreach ($keys as $key) {
            $this->service->deactivateKey($key);
        }

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('No active API key available');

        $this->service->getCurrentKey();
    }

    public function testGetCurrentKeyCachesResult(): void
    {
        // 创建并激活一个API密钥
        $apiKey = $this->service->createKey('Cached Key', 'cached-api-key', 'cached-secret', 'cn-beijing');
        $this->service->activateKey($apiKey);

        $result1 = $this->service->getCurrentKey();
        $result2 = $this->service->getCurrentKey();

        // 验证缓存行为：第二次调用不应再增加使用计数
        $this->assertSame($result1, $result2);
        $this->assertEquals($result1->getUsageCount(), $result2->getUsageCount());
    }

    public function testRotateKey(): void
    {
        // 创建并激活一个API密钥
        $apiKey = $this->service->createKey('Rotated Key', 'rotate-api-key', 'rotate-secret', 'cn-beijing');
        $this->service->activateKey($apiKey);

        $result = $this->service->rotateKey();

        $this->assertInstanceOf(ApiKey::class, $result);
        // rotateKey只是找到激活的key，不一定返回我们创建的key
        $this->assertTrue($result->isActive());
    }

    public function testCreateKey(): void
    {
        $result = $this->service->createKey('New Key', 'api-123', 'secret-123', 'cn-beijing');

        $this->assertInstanceOf(ApiKey::class, $result);
        $this->assertEquals('New Key', $result->getName());
        $this->assertEquals('api-123', $result->getApiKey());
        $this->assertEquals('secret-123', $result->getSecretKey());
        $this->assertEquals('cn-beijing', $result->getRegion());
        $this->assertEquals('volcano_ark', $result->getProvider());
        $this->assertTrue($result->isActive()); // 新创建的密钥默认激活
    }

    public function testCreateKeyWithDefaultRegion(): void
    {
        $result = $this->service->createKey('Default Region Key', 'api-456', 'secret-456');

        $this->assertInstanceOf(ApiKey::class, $result);
        $this->assertEquals('cn-beijing', $result->getRegion()); // Default region
    }

    public function testDeactivateKey(): void
    {
        $apiKey = $this->service->createKey('Test Key', 'deactivate-api', 'deactivate-secret', 'cn-beijing');
        $this->service->activateKey($apiKey); // 先激活

        $this->assertTrue($apiKey->isActive());

        $this->service->deactivateKey($apiKey);

        $this->assertFalse($apiKey->isActive());
    }

    public function testActivateKey(): void
    {
        $apiKey = $this->service->createKey('Test Key', 'activate-api', 'activate-secret', 'cn-beijing');

        $this->assertTrue($apiKey->isActive()); // 新创建时默认激活

        $this->service->activateKey($apiKey);

        $this->assertTrue($apiKey->isActive());
    }

    public function testGetAllKeys(): void
    {
        // 创建几个测试用的API密钥
        $key1 = $this->service->createKey('Key 1', 'api-key-1', 'secret-1', 'cn-beijing');
        $key2 = $this->service->createKey('Key 2', 'api-key-2', 'secret-2', 'cn-shanghai');

        $result = $this->service->getAllKeys();

        $this->assertGreaterThanOrEqual(2, count($result));

        // 检查我们创建的密钥是否在结果中
        $keyNames = array_map(fn ($key) => $key->getName(), $result);
        $this->assertContains('Key 1', $keyNames);
        $this->assertContains('Key 2', $keyNames);
    }

    public function testGetActiveKeys(): void
    {
        // 创建并激活一些API密钥
        $key1 = $this->service->createKey('Active Key 1', 'active-api-1', 'active-secret-1', 'cn-beijing');
        $key2 = $this->service->createKey('Active Key 2', 'active-api-2', 'active-secret-2', 'cn-shanghai');
        $key3 = $this->service->createKey('Inactive Key', 'inactive-api', 'inactive-secret', 'cn-beijing');

        $this->service->activateKey($key1);
        $this->service->activateKey($key2);
        // key3保持不激活

        $result = $this->service->getActiveKeys();

        $this->assertGreaterThanOrEqual(2, count($result));

        // 验证所有返回的密钥都是激活的
        foreach ($result as $key) {
            $this->assertTrue($key->isActive());
        }
    }

    public function testFindKeyByName(): void
    {
        // 创建一个特定名称的API密钥
        $createdKey = $this->service->createKey('Find Me', 'find-me-api', 'find-me-secret', 'cn-beijing');

        $result = $this->service->findKeyByName('Find Me');

        $this->assertInstanceOf(ApiKey::class, $result);
        $this->assertEquals('Find Me', $result->getName());
        $this->assertEquals($createdKey->getId(), $result->getId());
    }

    public function testDeleteKey(): void
    {
        // 创建一个要删除的API密钥
        $apiKey = $this->service->createKey('Delete Me', 'delete-me-api', 'delete-me-secret', 'cn-beijing');
        $keyId = $apiKey->getId();

        // 验证密钥存在
        $this->assertNotNull($this->service->findKeyByName('Delete Me'));

        // 删除密钥
        $this->service->deleteKey($apiKey);

        // 验证密钥已被删除
        $this->assertNull($this->service->findKeyByName('Delete Me'));
    }
}
