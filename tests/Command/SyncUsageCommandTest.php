<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\VolcanoArkApiBundle\Command\SyncUsageCommand;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Repository\ApiKeyRepository;
use Tourze\VolcanoArkApiBundle\Service\ApiKeyService;

/**
 * @internal
 */
#[CoversClass(SyncUsageCommand::class)]
#[RunTestsInSeparateProcesses]
class SyncUsageCommandTest extends AbstractCommandTestCase
{
    private ApiKeyRepository $apiKeyRepository;
    private ApiKeyService $apiKeyService;
    private CommandTester $commandTester;

    protected function onSetUp(): void
    {
        $this->apiKeyRepository = self::getService(ApiKeyRepository::class);
        $this->apiKeyService = self::getService(ApiKeyService::class);

        $command = self::getService(SyncUsageCommand::class);
        $this->assertInstanceOf(SyncUsageCommand::class, $command);
        $this->commandTester = new CommandTester($command);
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    public function testExecuteWithNoApiKeys(): void
    {
        // 清理数据库中的所有API Key
        $allKeys = $this->apiKeyRepository->findAll();
        foreach ($allKeys as $key) {
            self::getEntityManager()->remove($key);
        }
        self::getEntityManager()->flush();

        // 确保数据库中没有激活的API Key
        $this->assertCount(0, $this->apiKeyRepository->findActiveKeys());

        $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('No API keys found to sync', $this->commandTester->getDisplay());
    }

    public function testExecuteWithSpecificApiKey(): void
    {
        // 创建一个测试API Key
        $apiKey = $this->apiKeyService->createKey(
            'Test Key',
            'test-api-key',
            'test-secret-key',
            'cn-beijing'
        );

        $this->commandTester->execute([
            '--key-name' => 'Test Key',
            '--hours' => '1',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Syncing usage for key: Test Key', $this->commandTester->getDisplay());
    }

    public function testExecuteWithCustomDateRange(): void
    {
        // 创建一个测试API Key
        $this->apiKeyService->createKey(
            'DateRange Test Key',
            'date-range-api-key',
            'date-range-secret-key',
            'cn-beijing'
        );

        $this->commandTester->execute([
            '--start-date' => '2024-01-01 00:00:00',
            '--end-date' => '2024-01-01 23:59:59',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Period: 2024-01-01 00:00:00 to 2024-01-01 23:59:59', $this->commandTester->getDisplay());
    }

    public function testExecuteWithNonExistentApiKeyName(): void
    {
        $this->commandTester->execute([
            '--key-name' => 'NonExistent',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('No API keys found to sync', $this->commandTester->getDisplay());
    }

    public function testOptionStartDate(): void
    {
        $this->commandTester->execute([
            '--start-date' => '2024-01-01 00:00:00',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('2024-01-01 00:00:00', $this->commandTester->getDisplay());
    }

    public function testOptionEndDate(): void
    {
        $this->commandTester->execute([
            '--end-date' => '2024-01-01 23:59:59',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('2024-01-01 23:59:59', $this->commandTester->getDisplay());
    }

    public function testOptionKeyName(): void
    {
        // 创建一个测试API Key
        $this->apiKeyService->createKey(
            'KeyOption Test Key',
            'key-option-api-key',
            'key-option-secret-key',
            'cn-beijing'
        );

        $this->commandTester->execute([
            '--key-name' => 'KeyOption Test Key',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Syncing usage for key: KeyOption Test Key', $this->commandTester->getDisplay());
    }

    public function testOptionHours(): void
    {
        $this->commandTester->execute([
            '--hours' => '12',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        // Test that the command respects the hours option by checking the time period
        $this->assertStringContainsString('Period:', $this->commandTester->getDisplay());
    }

    public function testOptionForce(): void
    {
        // 创建一个测试API Key
        $this->apiKeyService->createKey(
            'Force Test Key',
            'force-api-key',
            'force-secret-key',
            'cn-beijing'
        );

        $this->commandTester->execute([
            '--force' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Syncing usage for key: Force Test Key', $this->commandTester->getDisplay());
    }
}