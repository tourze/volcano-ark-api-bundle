<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Entity\AuditLog;
use Tourze\VolcanoArkApiBundle\Repository\AuditLogRepository;

/**
 * @internal
 */
#[CoversClass(AuditLogRepository::class)]
#[RunTestsInSeparateProcesses]
class AuditLogRepositoryTest extends AbstractRepositoryTestCase
{
    protected function getRepository(): AuditLogRepository
    {
        return self::getService(AuditLogRepository::class);
    }

    protected function createNewEntity(): AuditLog
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

        $auditLog = new AuditLog();
        $auditLog->setApiKey($apiKey);
        $auditLog->setAction('test.action');
        $auditLog->setRequestData(['test' => true]);
        $auditLog->setResponseData(['success' => true]);
        $auditLog->setIsSuccess(true);

        return $auditLog;
    }

    protected function onSetUp(): void
    {
        // Let the DataFixtures load normally for all tests
        // Individual tests will handle their own data setup
    }

    public function testFindByApiKey(): void
    {
        $repository = $this->getRepository();
        $apiKey = new ApiKey();
        $apiKey->setName('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setIsActive(true);
        $apiKey->setRegion('cn-beijing');
        self::getEntityManager()->persist($apiKey);

        $auditLog1 = new AuditLog();
        $auditLog1->setApiKey($apiKey);
        $auditLog1->setAction('chat.completion');
        $auditLog1->setRequestData(['model' => 'test']);
        $auditLog1->setResponseData(['id' => 'test-123']);
        $auditLog1->setIsSuccess(true);
        self::getEntityManager()->persist($auditLog1);

        $auditLog2 = new AuditLog();
        $auditLog2->setApiKey($apiKey);
        $auditLog2->setAction('models.list');
        $auditLog2->setRequestData([]);
        $auditLog2->setResponseData(['models' => []]);
        $auditLog2->setIsSuccess(true);
        self::getEntityManager()->persist($auditLog2);

        $otherApiKey = new ApiKey();
        $otherApiKey->setName('Other API Key');
        $otherApiKey->setApiKey('other-key-456');
        $otherApiKey->setIsActive(true);
        $otherApiKey->setRegion('cn-beijing');
        self::getEntityManager()->persist($otherApiKey);

        $auditLog3 = new AuditLog();
        $auditLog3->setApiKey($otherApiKey);
        $auditLog3->setAction('chat.completion');
        $auditLog3->setRequestData(['model' => 'test']);
        $auditLog3->setResponseData(['id' => 'test-456']);
        $auditLog3->setIsSuccess(true);
        self::getEntityManager()->persist($auditLog3);

        self::getEntityManager()->flush();

        $apiKeyId = $apiKey->getId();
        $this->assertNotNull($apiKeyId);
        $results = $repository->findByApiKey($apiKeyId, 10);
        $this->assertCount(2, $results);
        $this->assertContains($auditLog1, $results);
        $this->assertContains($auditLog2, $results);
        $this->assertNotContains($auditLog3, $results);
    }

    public function testFindByDateRange(): void
    {
        $repository = $this->getRepository();
        $apiKey = new ApiKey();
        $apiKey->setName('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setIsActive(true);
        $apiKey->setRegion('cn-beijing');
        self::getEntityManager()->persist($apiKey);

        $now = new \DateTimeImmutable();
        $yesterday = new \DateTimeImmutable('-1 day');
        $tomorrow = new \DateTimeImmutable('+1 day');

        // Create a recent log
        $recentLog = new AuditLog();
        $recentLog->setApiKey($apiKey);
        $recentLog->setAction('chat.completion');
        $recentLog->setRequestData(['model' => 'test']);
        $recentLog->setResponseData(['id' => 'test-123']);
        $recentLog->setIsSuccess(true);
        self::getEntityManager()->persist($recentLog);

        // Create another recent log
        $anotherRecentLog = new AuditLog();
        $anotherRecentLog->setApiKey($apiKey);
        $anotherRecentLog->setAction('models.list');
        $anotherRecentLog->setRequestData(['model' => 'test2']);
        $anotherRecentLog->setResponseData(['id' => 'test-456']);
        $anotherRecentLog->setIsSuccess(true);
        self::getEntityManager()->persist($anotherRecentLog);

        self::getEntityManager()->flush();

        // Test behavior: find logs within date range
        $results = $repository->findByDateRange($yesterday, $tomorrow);

        // Should find at least our two recent logs
        $this->assertGreaterThanOrEqual(2, count($results));
        $this->assertContains($recentLog, $results);
        $this->assertContains($anotherRecentLog, $results);

        // Verify the logs are ordered by createTime DESC
        if (count($results) >= 2) {
            $firstLog = $results[0];
            $secondLog = $results[1];
            $this->assertGreaterThanOrEqual(
                $secondLog->getCreateTime()->getTimestamp(),
                $firstLog->getCreateTime()->getTimestamp(),
                'Logs should be ordered by createTime DESC'
            );
        }
    }

    public function testFindFailedLogs(): void
    {
        $repository = $this->getRepository();
        $apiKey = new ApiKey();
        $apiKey->setName('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setIsActive(true);
        $apiKey->setRegion('cn-beijing');
        self::getEntityManager()->persist($apiKey);

        $successLog = new AuditLog();
        $successLog->setApiKey($apiKey);
        $successLog->setAction('chat.completion');
        $successLog->setRequestData(['model' => 'test']);
        $successLog->setResponseData(['id' => 'test-123']);
        $successLog->setIsSuccess(true);
        self::getEntityManager()->persist($successLog);

        $failedLog = new AuditLog();
        $failedLog->setApiKey($apiKey);
        $failedLog->setAction('chat.completion');
        $failedLog->setRequestData(['model' => 'test']);
        $failedLog->setResponseData(['error' => 'API Error']);
        $failedLog->setIsSuccess(false);
        self::getEntityManager()->persist($failedLog);

        self::getEntityManager()->flush();

        $results = $repository->findFailedLogs(10);
        // Should find at least our failed log
        $this->assertGreaterThanOrEqual(1, count($results));
        $this->assertContains($failedLog, $results);
        // Success log should not be in failed logs
        foreach ($results as $result) {
            $this->assertFalse($result->isSuccess());
        }
    }

    public function testCountByApiKey(): void
    {
        $repository = $this->getRepository();
        $apiKey = new ApiKey();
        $apiKey->setName('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setIsActive(true);
        $apiKey->setRegion('cn-beijing');
        self::getEntityManager()->persist($apiKey);

        for ($i = 0; $i < 5; ++$i) {
            $auditLog = new AuditLog();
            $auditLog->setApiKey($apiKey);
            $auditLog->setAction('chat.completion');
            $auditLog->setRequestData(['model' => 'test']);
            $auditLog->setResponseData(['id' => 'test-' . $i]);
            $auditLog->setIsSuccess(true);
            self::getEntityManager()->persist($auditLog);
        }

        self::getEntityManager()->flush();

        $apiKeyId = $apiKey->getId();
        $this->assertNotNull($apiKeyId);
        $count = $repository->countByApiKey($apiKeyId);
        $this->assertEquals(5, $count);
    }

    public function testRemoveOldLogs(): void
    {
        $repository = $this->getRepository();
        $apiKey = new ApiKey();
        $apiKey->setName('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setIsActive(true);
        $apiKey->setRegion('cn-beijing');
        self::getEntityManager()->persist($apiKey);

        $auditLog = new AuditLog();
        $auditLog->setApiKey($apiKey);
        $auditLog->setAction('chat.completion');
        $auditLog->setRequestData(['model' => 'test']);
        $auditLog->setResponseData(['id' => 'test-123']);
        $auditLog->setIsSuccess(true);
        self::getEntityManager()->persist($auditLog);
        self::getEntityManager()->flush();

        $id = $auditLog->getId();
        $this->assertNotNull($repository->find($id));

        // Remove logs older than tomorrow (which includes our log)
        $cutoffDate = new \DateTimeImmutable('+1 day');
        $deletedCount = $repository->removeOldLogs($cutoffDate);
        // Should delete at least our log
        $this->assertGreaterThanOrEqual(1, $deletedCount);

        // Clear the entity manager cache to ensure we get fresh data
        self::getEntityManager()->clear();
        $this->assertNull($repository->find($id));
    }

    public function testRemove(): void
    {
        $repository = $this->getRepository();

        $apiKey = new ApiKey();
        $apiKey->setName('Test API Key');
        $apiKey->setApiKey('test-key-remove');
        $apiKey->setIsActive(true);
        self::getEntityManager()->persist($apiKey);

        $auditLog = new AuditLog();
        $auditLog->setApiKey($apiKey);
        $auditLog->setAction('test.remove');
        $auditLog->setRequestData(['test' => true]);
        $auditLog->setResponseData(['success' => true]);
        $auditLog->setIsSuccess(true);
        self::getEntityManager()->persist($auditLog);
        self::getEntityManager()->flush();

        $id = $auditLog->getId();
        $this->assertNotNull($id);

        // Test remove with flush
        $repository->remove($auditLog, true);

        self::getEntityManager()->clear();
        $foundLog = $repository->find($id);
        $this->assertNull($foundLog);

        // Test remove without flush
        // Re-fetch the apiKey to get a managed instance
        $apiKeyId = $apiKey->getId();
        $this->assertNotNull($apiKeyId);
        $apiKey = self::getEntityManager()->find(ApiKey::class, $apiKeyId);
        $this->assertNotNull($apiKey);
        $auditLog2 = new AuditLog();
        $auditLog2->setApiKey($apiKey);
        $auditLog2->setAction('test.remove2');
        $auditLog2->setRequestData(['test' => true]);
        $auditLog2->setResponseData(['success' => true]);
        $auditLog2->setIsSuccess(true);
        self::getEntityManager()->persist($auditLog2);
        self::getEntityManager()->flush();

        $id2 = $auditLog2->getId();
        $this->assertNotNull($id2);

        $repository->remove($auditLog2, false);

        // Should still exist before flush
        $foundLog2 = $repository->find($id2);
        $this->assertNotNull($foundLog2);

        self::getEntityManager()->flush();
        self::getEntityManager()->clear();

        $foundLog2 = $repository->find($id2);
        $this->assertNull($foundLog2);
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

        $auditLog = new AuditLog();
        $auditLog->setApiKey($apiKey);
        $auditLog->setAction('test.save');
        $auditLog->setRequestData(['test' => true]);
        $auditLog->setResponseData(['success' => true]);
        $auditLog->setIsSuccess(true);

        // Test save with flush
        $repository->save($auditLog, true);
        $this->assertNotNull($auditLog->getId());

        // Test save without flush
        $auditLog2 = new AuditLog();
        $auditLog2->setApiKey($apiKey);
        $auditLog2->setAction('test.save2');
        $auditLog2->setRequestData(['test2' => true]);
        $auditLog2->setResponseData(['success2' => true]);
        $auditLog2->setIsSuccess(false);

        $repository->save($auditLog2, false);
        self::getEntityManager()->flush();
        $this->assertNotNull($auditLog2->getId());
    }
}
