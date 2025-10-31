<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Repository\ApiKeyRepository;

/**
 * @internal
 */
#[CoversClass(ApiKeyRepository::class)]
#[RunTestsInSeparateProcesses]
class ApiKeyRepositoryTest extends AbstractRepositoryTestCase
{
    protected function getRepository(): ApiKeyRepository
    {
        return self::getService(ApiKeyRepository::class);
    }

    protected function createNewEntity(): ApiKey
    {
        $apiKey = new ApiKey();
        $apiKey->setName('Test Key ' . uniqid());
        $apiKey->setApiKey('test-key-' . uniqid());
        $apiKey->setSecretKey('test-secret-' . uniqid());
        $apiKey->setIsActive(true);
        $apiKey->setRegion('cn-beijing');
        $apiKey->setProvider('volcano_ark');

        return $apiKey;
    }

    protected function onSetUp(): void
    {
        // Let the DataFixtures load normally for all tests
        // Individual tests will handle their own data setup
    }

    protected function cleanUpApiKeys(): void
    {
        $repository = $this->getRepository();
        $allKeys = $repository->findAll();
        foreach ($allKeys as $key) {
            self::getEntityManager()->remove($key);
        }
        self::getEntityManager()->flush();
    }

    public function testFindActiveKey(): void
    {
        $repository = $this->getRepository();

        // Clean up any existing data
        $this->cleanUpApiKeys();

        // Create inactive key
        $inactiveKey = new ApiKey();
        $inactiveKey->setName('Inactive Key');
        $inactiveKey->setApiKey('inactive-key-123');
        $inactiveKey->setSecretKey('inactive-secret-123');
        $inactiveKey->setIsActive(false);
        $inactiveKey->setRegion('cn-beijing');
        $inactiveKey->setProvider('volcano_ark');
        self::getEntityManager()->persist($inactiveKey);

        // Create active key with higher usage count
        $activeKeyHigh = new ApiKey();
        $activeKeyHigh->setName('Active Key High');
        $activeKeyHigh->setApiKey('active-key-high-123');
        $activeKeyHigh->setSecretKey('active-secret-high-123');
        $activeKeyHigh->setIsActive(true);
        $activeKeyHigh->setRegion('cn-beijing');
        $activeKeyHigh->setProvider('volcano_ark');
        $activeKeyHigh->setUsageCount(10);
        self::getEntityManager()->persist($activeKeyHigh);

        // Create active key with lower usage count (should be returned)
        $activeKeyLow = new ApiKey();
        $activeKeyLow->setName('Active Key Low');
        $activeKeyLow->setApiKey('active-key-low-123');
        $activeKeyLow->setSecretKey('active-secret-low-123');
        $activeKeyLow->setIsActive(true);
        $activeKeyLow->setRegion('cn-beijing');
        $activeKeyLow->setProvider('volcano_ark');
        $activeKeyLow->setUsageCount(2);
        self::getEntityManager()->persist($activeKeyLow);

        self::getEntityManager()->flush();

        $result = $repository->findActiveKey();

        $this->assertNotNull($result);
        $this->assertEquals('Active Key Low', $result->getName());
        $this->assertEquals(2, $result->getUsageCount());
    }

    public function testFindActiveKeys(): void
    {
        $repository = $this->getRepository();

        // Clean up any existing data
        $this->cleanUpApiKeys();

        // Create inactive key
        $inactiveKey = new ApiKey();
        $inactiveKey->setName('Inactive Key');
        $inactiveKey->setApiKey('inactive-key-123');
        $inactiveKey->setSecretKey('inactive-secret-123');
        $inactiveKey->setIsActive(false);
        $inactiveKey->setRegion('cn-beijing');
        $inactiveKey->setProvider('volcano_ark');
        self::getEntityManager()->persist($inactiveKey);

        // Create active keys
        $activeKey1 = new ApiKey();
        $activeKey1->setName('Active Key 1');
        $activeKey1->setApiKey('active-key-1-123');
        $activeKey1->setSecretKey('active-secret-1-123');
        $activeKey1->setIsActive(true);
        $activeKey1->setRegion('cn-beijing');
        $activeKey1->setProvider('volcano_ark');
        $activeKey1->setUsageCount(5);
        self::getEntityManager()->persist($activeKey1);

        $activeKey2 = new ApiKey();
        $activeKey2->setName('Active Key 2');
        $activeKey2->setApiKey('active-key-2-123');
        $activeKey2->setSecretKey('active-secret-2-123');
        $activeKey2->setIsActive(true);
        $activeKey2->setRegion('cn-beijing');
        $activeKey2->setProvider('volcano_ark');
        $activeKey2->setUsageCount(3);
        self::getEntityManager()->persist($activeKey2);

        self::getEntityManager()->flush();

        $results = $repository->findActiveKeys();

        $this->assertCount(2, $results);
        // Should be ordered by usage count ASC
        $this->assertEquals('Active Key 2', $results[0]->getName());
        $this->assertEquals(3, $results[0]->getUsageCount());
        $this->assertEquals('Active Key 1', $results[1]->getName());
        $this->assertEquals(5, $results[1]->getUsageCount());
    }

    public function testFindByName(): void
    {
        $repository = $this->getRepository();

        $apiKey = new ApiKey();
        $apiKey->setName('Unique Test Key');
        $apiKey->setApiKey('unique-test-key-123');
        $apiKey->setSecretKey('unique-secret-123');
        $apiKey->setIsActive(true);
        $apiKey->setRegion('cn-beijing');
        $apiKey->setProvider('volcano_ark');
        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->flush();

        $result = $repository->findByName('Unique Test Key');
        $this->assertNotNull($result);
        $this->assertEquals('Unique Test Key', $result->getName());

        $nullResult = $repository->findByName('Non Existent Key');
        $this->assertNull($nullResult);
    }

    public function testSave(): void
    {
        $repository = $this->getRepository();

        $apiKey = new ApiKey();
        $apiKey->setName('Save Test Key');
        $apiKey->setApiKey('save-test-key-123');
        $apiKey->setSecretKey('save-secret-123');
        $apiKey->setIsActive(true);
        $apiKey->setRegion('cn-beijing');
        $apiKey->setProvider('volcano_ark');

        $repository->save($apiKey);

        $this->assertNotNull($apiKey->getId());

        // Verify it was saved
        $found = $repository->find($apiKey->getId());
        $this->assertNotNull($found);
        $this->assertEquals('Save Test Key', $found->getName());
    }

    public function testRemove(): void
    {
        $repository = $this->getRepository();

        $apiKey = new ApiKey();
        $apiKey->setName('Remove Test Key');
        $apiKey->setApiKey('remove-test-key-123');
        $apiKey->setSecretKey('remove-secret-123');
        $apiKey->setIsActive(true);
        $apiKey->setRegion('cn-beijing');
        $apiKey->setProvider('volcano_ark');

        $repository->save($apiKey);
        $id = $apiKey->getId();

        $this->assertNotNull($repository->find($id));

        $repository->remove($apiKey);

        $this->assertNull($repository->find($id));
    }

    public function testFindActiveAndValidKeys(): void
    {
        $repository = $this->getRepository();

        // Clean up any existing data
        $this->cleanUpApiKeys();

        // Create test keys
        $activeKey = new ApiKey();
        $activeKey->setName('Active Valid Key');
        $activeKey->setApiKey('active-valid-key-123');
        $activeKey->setSecretKey('active-valid-secret-123');
        $activeKey->setIsActive(true);
        $activeKey->setRegion('cn-beijing');
        $activeKey->setProvider('volcano_ark');
        self::getEntityManager()->persist($activeKey);

        $inactiveKey = new ApiKey();
        $inactiveKey->setName('Inactive Key');
        $inactiveKey->setApiKey('inactive-key-123');
        $inactiveKey->setSecretKey('inactive-secret-123');
        $inactiveKey->setIsActive(false);
        $inactiveKey->setRegion('cn-beijing');
        $inactiveKey->setProvider('volcano_ark');
        self::getEntityManager()->persist($inactiveKey);

        self::getEntityManager()->flush();

        $results = $repository->findActiveAndValidKeys();

        $this->assertCount(1, $results);
        $this->assertEquals('Active Valid Key', $results[0]->getName());
        $this->assertTrue($results[0]->isActive());
    }

    public function testFindByPriority(): void
    {
        $repository = $this->getRepository();

        // Clean up any existing data
        $this->cleanUpApiKeys();

        $now = new \DateTimeImmutable();
        $earlier = $now->modify('-1 hour');

        // Key with lower usage count but later last used
        $key1 = new ApiKey();
        $key1->setName('Priority Key 1');
        $key1->setApiKey('priority-key-1-123');
        $key1->setSecretKey('priority-secret-1-123');
        $key1->setIsActive(true);
        $key1->setRegion('cn-beijing');
        $key1->setProvider('volcano_ark');
        $key1->setUsageCount(1);
        self::getEntityManager()->persist($key1);

        // Key with higher usage count but earlier last used
        $key2 = new ApiKey();
        $key2->setName('Priority Key 2');
        $key2->setApiKey('priority-key-2-123');
        $key2->setSecretKey('priority-secret-2-123');
        $key2->setIsActive(true);
        $key2->setRegion('cn-beijing');
        $key2->setProvider('volcano_ark');
        $key2->setUsageCount(2);
        self::getEntityManager()->persist($key2);

        // Key with same usage count as key1 but earlier last used
        $key3 = new ApiKey();
        $key3->setName('Priority Key 3');
        $key3->setApiKey('priority-key-3-123');
        $key3->setSecretKey('priority-secret-3-123');
        $key3->setIsActive(true);
        $key3->setRegion('cn-beijing');
        $key3->setProvider('volcano_ark');
        $key3->setUsageCount(1);
        self::getEntityManager()->persist($key3);

        // Set lastUsedTime for testing
        $key1->setLastUsedTime($now);
        $key2->setLastUsedTime($earlier);
        $key3->setLastUsedTime($earlier);

        self::getEntityManager()->flush();

        $results = $repository->findByPriority();

        $this->assertCount(3, $results);
        // Order should be: usage count ASC, then lastUsedTime ASC
        $this->assertEquals('Priority Key 3', $results[0]->getName()); // Usage 1, earlier time
        $this->assertEquals('Priority Key 1', $results[1]->getName()); // Usage 1, later time
        $this->assertEquals('Priority Key 2', $results[2]->getName()); // Usage 2
    }

    public function testFindActiveKeyWithNoActiveKeys(): void
    {
        $repository = $this->getRepository();

        // Clean up any existing data
        $this->cleanUpApiKeys();

        $result = $repository->findActiveKey();
        $this->assertNull($result);
    }

    public function testFindByNameWithDifferentProvider(): void
    {
        $repository = $this->getRepository();

        $apiKey = new ApiKey();
        $apiKey->setName('Different Provider Key');
        $apiKey->setApiKey('different-provider-key-123');
        $apiKey->setSecretKey('different-secret-123');
        $apiKey->setIsActive(true);
        $apiKey->setRegion('cn-beijing');
        $apiKey->setProvider('other_provider'); // Different provider
        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->flush();

        $result = $repository->findByName('Different Provider Key');
        $this->assertNull($result); // Should not find because provider is different
    }
}
