<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Entity\ApiKeyUsage;
use Tourze\VolcanoArkApiBundle\Repository\ApiKeyRepository;
use Tourze\VolcanoArkApiBundle\Repository\ApiKeyUsageRepository;

/**
 * @internal
 */
#[CoversClass(ApiKeyUsageRepository::class)]
#[RunTestsInSeparateProcesses]
class ApiKeyUsageRepositoryTest extends AbstractRepositoryTestCase
{
    protected function getRepository(): ApiKeyUsageRepository
    {
        return self::getService(ApiKeyUsageRepository::class);
    }

    protected function createNewEntity(): ApiKeyUsage
    {
        $apiKey = new ApiKey();
        $apiKey->setName('Test Key ' . uniqid());
        $apiKey->setApiKey('test-key-' . uniqid());
        $apiKey->setSecretKey('test-secret-' . uniqid());
        $apiKey->setIsActive(true);
        $apiKey->setRegion('cn-beijing');
        $apiKey->setProvider('volcano_ark');
        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->flush();

        $usage = new ApiKeyUsage();
        $usage->setApiKey($apiKey);
        $usage->setUsageHour(new \DateTimeImmutable());
        $usage->setPromptTokens(100);
        $usage->setCompletionTokens(50);
        $usage->setRequestCount(1);

        return $usage;
    }

    protected function onSetUp(): void
    {
        // Additional setup if needed
    }

    protected function cleanUpApiKeys(): void
    {
        $apiKeyRepository = self::getService(ApiKeyRepository::class);
        $allKeys = $apiKeyRepository->findAll();
        foreach ($allKeys as $key) {
            self::getEntityManager()->remove($key);
        }

        $usageRepository = $this->getRepository();
        $allUsages = $usageRepository->findAll();
        foreach ($allUsages as $usage) {
            self::getEntityManager()->remove($usage);
        }

        self::getEntityManager()->flush();
    }

    public function testFindOrCreateByKeyAndHour(): void
    {
        $repository = $this->getRepository();

        $apiKey = new ApiKey();
        $apiKey->setName('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setSecretKey('test-secret-123');
        $apiKey->setIsActive(true);
        $apiKey->setRegion('cn-beijing');
        $apiKey->setProvider('volcano_ark');
        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->flush();

        $hour = new \DateTimeImmutable('2024-01-01 10:00:00');

        // Test creating new usage
        $usage1 = $repository->findOrCreateByKeyAndHour($apiKey, $hour, 'endpoint-123');
        $this->assertNotNull($usage1);
        $this->assertEquals($apiKey, $usage1->getApiKey());
        $this->assertEquals($hour, $usage1->getUsageHour());
        $this->assertEquals('endpoint-123', $usage1->getEndpointId());

        self::getEntityManager()->flush();

        // Test finding existing usage
        $usage2 = $repository->findOrCreateByKeyAndHour($apiKey, $hour, 'endpoint-123');
        $this->assertSame($usage1, $usage2);
    }

    public function testFindOrCreateByKeyAndHourWithoutEndpoint(): void
    {
        $repository = $this->getRepository();

        $apiKey = new ApiKey();
        $apiKey->setName('Test API Key');
        $apiKey->setApiKey('test-key-456');
        $apiKey->setSecretKey('test-secret-456');
        $apiKey->setIsActive(true);
        $apiKey->setRegion('cn-beijing');
        $apiKey->setProvider('volcano_ark');
        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->flush();

        $hour = new \DateTimeImmutable('2024-01-01 11:00:00');

        $usage = $repository->findOrCreateByKeyAndHour($apiKey, $hour);
        $this->assertNotNull($usage);
        $this->assertNull($usage->getEndpointId());
    }

    public function testFindByApiKeyAndDateRange(): void
    {
        $repository = $this->getRepository();

        $apiKey1 = new ApiKey();
        $apiKey1->setName('Test API Key 1');
        $apiKey1->setApiKey('test-key-1');
        $apiKey1->setSecretKey('test-secret-1');
        $apiKey1->setIsActive(true);
        $apiKey1->setRegion('cn-beijing');
        $apiKey1->setProvider('volcano_ark');
        self::getEntityManager()->persist($apiKey1);

        $apiKey2 = new ApiKey();
        $apiKey2->setName('Test API Key 2');
        $apiKey2->setApiKey('test-key-2');
        $apiKey2->setSecretKey('test-secret-2');
        $apiKey2->setIsActive(true);
        $apiKey2->setRegion('cn-beijing');
        $apiKey2->setProvider('volcano_ark');
        self::getEntityManager()->persist($apiKey2);

        // Create usage records
        $usage1 = new ApiKeyUsage();
        $usage1->setApiKey($apiKey1);
        $usage1->setUsageHour(new \DateTimeImmutable('2024-01-01 10:00:00'));
        $usage1->setPromptTokens(100);
        self::getEntityManager()->persist($usage1);

        $usage2 = new ApiKeyUsage();
        $usage2->setApiKey($apiKey1);
        $usage2->setUsageHour(new \DateTimeImmutable('2024-01-01 11:00:00'));
        $usage2->setPromptTokens(150);
        self::getEntityManager()->persist($usage2);

        $usage3 = new ApiKeyUsage();
        $usage3->setApiKey($apiKey2);
        $usage3->setUsageHour(new \DateTimeImmutable('2024-01-01 10:30:00'));
        $usage3->setPromptTokens(200);
        self::getEntityManager()->persist($usage3);

        $usage4 = new ApiKeyUsage();
        $usage4->setApiKey($apiKey1);
        $usage4->setUsageHour(new \DateTimeImmutable('2024-01-01 12:00:00')); // Outside range
        $usage4->setPromptTokens(75);
        self::getEntityManager()->persist($usage4);

        self::getEntityManager()->flush();

        $startDate = new \DateTimeImmutable('2024-01-01 09:00:00');
        $endDate = new \DateTimeImmutable('2024-01-01 11:30:00');

        $results = $repository->findByApiKeyAndDateRange($apiKey1, $startDate, $endDate);

        $this->assertCount(2, $results);
        $this->assertEquals($usage1, $results[0]);
        $this->assertEquals($usage2, $results[1]);
    }

    public function testFindByDateRange(): void
    {
        $repository = $this->getRepository();

        // Clean up any existing data
        $this->cleanUpApiKeys();

        $apiKey1 = new ApiKey();
        $apiKey1->setName('Test API Key 1');
        $apiKey1->setApiKey('test-key-1');
        $apiKey1->setSecretKey('test-secret-1');
        $apiKey1->setIsActive(true);
        $apiKey1->setRegion('cn-beijing');
        $apiKey1->setProvider('volcano_ark');
        self::getEntityManager()->persist($apiKey1);

        $apiKey2 = new ApiKey();
        $apiKey2->setName('Test API Key 2');
        $apiKey2->setApiKey('test-key-2');
        $apiKey2->setSecretKey('test-secret-2');
        $apiKey2->setIsActive(true);
        $apiKey2->setRegion('cn-beijing');
        $apiKey2->setProvider('volcano_ark');
        self::getEntityManager()->persist($apiKey2);

        // Create usage records in date range
        $usage1 = new ApiKeyUsage();
        $usage1->setApiKey($apiKey1);
        $usage1->setUsageHour(new \DateTimeImmutable('2024-01-01 10:00:00'));
        $usage1->setPromptTokens(100);
        self::getEntityManager()->persist($usage1);

        $usage2 = new ApiKeyUsage();
        $usage2->setApiKey($apiKey2);
        $usage2->setUsageHour(new \DateTimeImmutable('2024-01-01 10:00:00'));
        $usage2->setPromptTokens(150);
        self::getEntityManager()->persist($usage2);

        $usage3 = new ApiKeyUsage();
        $usage3->setApiKey($apiKey1);
        $usage3->setUsageHour(new \DateTimeImmutable('2024-01-01 11:00:00'));
        $usage3->setPromptTokens(200);
        self::getEntityManager()->persist($usage3);

        // Outside date range
        $usage4 = new ApiKeyUsage();
        $usage4->setApiKey($apiKey1);
        $usage4->setUsageHour(new \DateTimeImmutable('2024-01-01 12:00:00'));
        $usage4->setPromptTokens(75);
        self::getEntityManager()->persist($usage4);

        self::getEntityManager()->flush();

        $startDate = new \DateTimeImmutable('2024-01-01 09:00:00');
        $endDate = new \DateTimeImmutable('2024-01-01 11:30:00');

        $results = $repository->findByDateRange($startDate, $endDate);

        $this->assertCount(3, $results);
        // Should be ordered by usage hour, then by api key
        $this->assertEquals($usage1->getPromptTokens(), $results[0]->getPromptTokens());
        $this->assertEquals($usage2->getPromptTokens(), $results[1]->getPromptTokens());
        $this->assertEquals($usage3->getPromptTokens(), $results[2]->getPromptTokens());
    }

    public function testGetTotalUsageByApiKey(): void
    {
        $repository = $this->getRepository();

        $apiKey = new ApiKey();
        $apiKey->setName('Test API Key');
        $apiKey->setApiKey('test-key-total');
        $apiKey->setSecretKey('test-secret-total');
        $apiKey->setIsActive(true);
        $apiKey->setRegion('cn-beijing');
        $apiKey->setProvider('volcano_ark');
        self::getEntityManager()->persist($apiKey);

        $otherApiKey = new ApiKey();
        $otherApiKey->setName('Other API Key');
        $otherApiKey->setApiKey('other-key');
        $otherApiKey->setSecretKey('other-secret');
        $otherApiKey->setIsActive(true);
        $otherApiKey->setRegion('cn-beijing');
        $otherApiKey->setProvider('volcano_ark');
        self::getEntityManager()->persist($otherApiKey);

        // Create usage records for our test key
        $usage1 = new ApiKeyUsage();
        $usage1->setApiKey($apiKey);
        $usage1->setUsageHour(new \DateTimeImmutable('2024-01-01 10:00:00'));
        $usage1->setPromptTokens(100);
        $usage1->setCompletionTokens(50);
        $usage1->setTotalTokens(150);
        $usage1->setRequestCount(5);
        $usage1->setEstimatedCost('1.50');
        self::getEntityManager()->persist($usage1);

        $usage2 = new ApiKeyUsage();
        $usage2->setApiKey($apiKey);
        $usage2->setUsageHour(new \DateTimeImmutable('2024-01-01 11:00:00'));
        $usage2->setPromptTokens(200);
        $usage2->setCompletionTokens(100);
        $usage2->setTotalTokens(300);
        $usage2->setRequestCount(3);
        $usage2->setEstimatedCost('2.75');
        self::getEntityManager()->persist($usage2);

        // Create usage record for other key (should not be included)
        $usage3 = new ApiKeyUsage();
        $usage3->setApiKey($otherApiKey);
        $usage3->setUsageHour(new \DateTimeImmutable('2024-01-01 12:00:00'));
        $usage3->setPromptTokens(500);
        $usage3->setCompletionTokens(250);
        $usage3->setTotalTokens(750);
        $usage3->setRequestCount(10);
        $usage3->setEstimatedCost('5.00');
        self::getEntityManager()->persist($usage3);

        self::getEntityManager()->flush();

        $result = $repository->getTotalUsageByApiKey($apiKey);

        $this->assertEquals(300, $result['totalPromptTokens']);
        $this->assertEquals(150, $result['totalCompletionTokens']);
        $this->assertEquals(450, $result['totalTokens']);
        $this->assertEquals(8, $result['totalRequests']);
        $this->assertEquals(4.25, $result['totalCost']);
    }

    public function testGetTotalUsageByApiKeyWithNoUsage(): void
    {
        $repository = $this->getRepository();

        $apiKey = new ApiKey();
        $apiKey->setName('Unused API Key');
        $apiKey->setApiKey('unused-key');
        $apiKey->setSecretKey('unused-secret');
        $apiKey->setIsActive(true);
        $apiKey->setRegion('cn-beijing');
        $apiKey->setProvider('volcano_ark');
        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->flush();

        $result = $repository->getTotalUsageByApiKey($apiKey);

        $this->assertNull($result['totalPromptTokens']);
        $this->assertNull($result['totalCompletionTokens']);
        $this->assertNull($result['totalTokens']);
        $this->assertNull($result['totalRequests']);
        $this->assertNull($result['totalCost']);
    }

    public function testFindOrCreateDistinctEndpoints(): void
    {
        $repository = $this->getRepository();

        $apiKey = new ApiKey();
        $apiKey->setName('Test API Key');
        $apiKey->setApiKey('test-key-endpoints');
        $apiKey->setSecretKey('test-secret-endpoints');
        $apiKey->setIsActive(true);
        $apiKey->setRegion('cn-beijing');
        $apiKey->setProvider('volcano_ark');
        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->flush();

        $hour = new \DateTimeImmutable('2024-01-01 10:00:00');

        // Create usage for different endpoints
        $usage1 = $repository->findOrCreateByKeyAndHour($apiKey, $hour, 'endpoint-1');
        $usage2 = $repository->findOrCreateByKeyAndHour($apiKey, $hour, 'endpoint-2');
        $usage3 = $repository->findOrCreateByKeyAndHour($apiKey, $hour, null); // No endpoint

        self::getEntityManager()->flush();

        $this->assertNotSame($usage1, $usage2);
        $this->assertNotSame($usage1, $usage3);
        $this->assertNotSame($usage2, $usage3);

        $this->assertEquals('endpoint-1', $usage1->getEndpointId());
        $this->assertEquals('endpoint-2', $usage2->getEndpointId());
        $this->assertNull($usage3->getEndpointId());
    }

    public function testRemove(): void
    {
        $repository = $this->getRepository();

        $apiKey = new ApiKey();
        $apiKey->setName('Test API Key');
        $apiKey->setApiKey('test-key-remove');
        $apiKey->setSecretKey('test-secret-remove');
        $apiKey->setIsActive(true);
        self::getEntityManager()->persist($apiKey);

        $usage = new ApiKeyUsage();
        $usage->setApiKey($apiKey);
        $usage->setUsageHour(new \DateTimeImmutable());
        $usage->setPromptTokens(100);
        $usage->setCompletionTokens(50);
        $usage->setRequestCount(1);
        self::getEntityManager()->persist($usage);
        self::getEntityManager()->flush();

        $id = $usage->getId();
        $this->assertNotNull($id);

        // Test remove with flush
        $repository->remove($usage, true);

        self::getEntityManager()->clear();
        $foundUsage = $repository->find($id);
        $this->assertNull($foundUsage);

        // Test remove without flush
        $usage2 = new ApiKeyUsage();
        $usage2->setApiKey($apiKey);
        $usage2->setUsageHour(new \DateTimeImmutable());
        $usage2->setPromptTokens(200);
        $usage2->setCompletionTokens(100);
        $usage2->setRequestCount(2);
        self::getEntityManager()->persist($usage2);
        self::getEntityManager()->flush();

        $id2 = $usage2->getId();
        $this->assertNotNull($id2);

        $repository->remove($usage2, false);

        // Should still exist before flush
        $foundUsage2 = $repository->find($id2);
        $this->assertNotNull($foundUsage2);

        self::getEntityManager()->flush();
        self::getEntityManager()->clear();

        $foundUsage2 = $repository->find($id2);
        $this->assertNull($foundUsage2);
    }

    public function testSave(): void
    {
        $repository = $this->getRepository();

        $apiKey = new ApiKey();
        $apiKey->setName('Save Test API Key');
        $apiKey->setApiKey('save-test-key');
        $apiKey->setSecretKey('save-test-secret');
        $apiKey->setIsActive(true);
        $apiKey->setRegion('cn-beijing');
        $apiKey->setProvider('volcano_ark');
        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->flush();

        $usage = new ApiKeyUsage();
        $usage->setApiKey($apiKey);
        $usage->setUsageHour(new \DateTimeImmutable());
        $usage->setPromptTokens(100);
        $usage->setCompletionTokens(50);
        $usage->setRequestCount(1);

        // Test save with flush
        $repository->save($usage, true);
        $this->assertNotNull($usage->getId());

        // Test save without flush
        $usage2 = new ApiKeyUsage();
        $usage2->setApiKey($apiKey);
        $usage2->setUsageHour(new \DateTimeImmutable());
        $usage2->setPromptTokens(200);
        $usage2->setCompletionTokens(100);
        $usage2->setRequestCount(2);

        $repository->save($usage2, false);
        self::getEntityManager()->flush();
        $this->assertNotNull($usage2->getId());
    }
}
