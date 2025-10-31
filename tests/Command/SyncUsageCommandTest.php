<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\VolcanoArkApiBundle\Command\SyncUsageCommand;
use Tourze\VolcanoArkApiBundle\DTO\UsageMetricItem;
use Tourze\VolcanoArkApiBundle\DTO\UsageMetricValue;
use Tourze\VolcanoArkApiBundle\DTO\UsageResult;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Entity\ApiKeyUsage;
use Tourze\VolcanoArkApiBundle\Repository\ApiKeyRepository;
use Tourze\VolcanoArkApiBundle\Repository\ApiKeyUsageRepository;
use Tourze\VolcanoArkApiBundle\Service\UsageService;

/**
 * @internal
 */
#[CoversClass(SyncUsageCommand::class)]
#[RunTestsInSeparateProcesses]
class SyncUsageCommandTest extends AbstractCommandTestCase
{
    private ApiKeyRepository&MockObject $apiKeyRepository;

    private ApiKeyUsageRepository&MockObject $apiKeyUsageRepository;

    private UsageService&MockObject $usageService;

    private CommandTester $commandTester;

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        $kernel = self::$kernel;
        $this->assertNotNull($kernel, 'Kernel should not be null');
        $application = new Application($kernel);

        $this->apiKeyRepository = $this->createMock(ApiKeyRepository::class);
        $this->apiKeyUsageRepository = $this->createMock(ApiKeyUsageRepository::class);
        $this->usageService = $this->createMock(UsageService::class);

        self::getContainer()->set(ApiKeyRepository::class, $this->apiKeyRepository);
        self::getContainer()->set(ApiKeyUsageRepository::class, $this->apiKeyUsageRepository);
        self::getContainer()->set(UsageService::class, $this->usageService);

        $command = self::getContainer()->get(SyncUsageCommand::class);
        $this->assertInstanceOf(SyncUsageCommand::class, $command, 'Command should be instance of SyncUsageCommand');
        $application->add($command);

        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteWithNoApiKeys(): void
    {
        $this->apiKeyRepository->expects($this->once())
            ->method('findActiveKeys')
            ->willReturn([])
        ;

        $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('No API keys found to sync', $this->commandTester->getDisplay());
    }

    public function testExecuteWithSpecificApiKey(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setName('Test Key');
        $apiKey->setApiKey('test-key-123');

        $this->apiKeyRepository->expects($this->once())
            ->method('findByName')
            ->with('Test Key')
            ->willReturn($apiKey)
        ;

        $this->apiKeyUsageRepository->expects($this->atLeastOnce())
            ->method('findByApiKeyAndDateRange')
            ->willReturn([])
        ;

        $this->usageService->expects($this->atLeastOnce())
            ->method('getUsageForApiKey')
            ->willReturn([])
        ;

        // EntityManager flush will be called by the command

        $this->commandTester->execute([
            '--key-name' => 'Test Key',
            '--hours' => '1',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Syncing usage for key: Test Key', $this->commandTester->getDisplay());
    }

    public function testExecuteWithCustomDateRange(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setName('Test Key');

        $this->apiKeyRepository->expects($this->once())
            ->method('findActiveKeys')
            ->willReturn([$apiKey])
        ;

        $this->apiKeyUsageRepository->expects($this->atLeastOnce())
            ->method('findByApiKeyAndDateRange')
            ->willReturn([])
        ;

        $this->usageService->expects($this->atLeastOnce())
            ->method('getUsageForApiKey')
            ->willReturn([])
        ;

        // EntityManager flush will be called by the command

        $this->commandTester->execute([
            '--start-date' => '2024-01-01 00:00:00',
            '--end-date' => '2024-01-01 23:59:59',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Period: 2024-01-01 00:00:00 to 2024-01-01 23:59:59', $this->commandTester->getDisplay());
    }

    public function testExecuteWithExistingDataAndNoForce(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setName('Test Key');

        $existingUsage = new ApiKeyUsage();
        $existingUsage->setApiKey($apiKey);

        $this->apiKeyRepository->expects($this->once())
            ->method('findActiveKeys')
            ->willReturn([$apiKey])
        ;

        $this->apiKeyUsageRepository->expects($this->atLeastOnce())
            ->method('findByApiKeyAndDateRange')
            ->willReturn([$existingUsage])
        ;

        $this->usageService->expects($this->never())
            ->method('getUsageForApiKey')
        ;

        // EntityManager flush will be called by the command

        $this->commandTester->execute([
            '--hours' => '1',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('already synced', $this->commandTester->getDisplay());
    }

    public function testExecuteWithForceResync(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setName('Test Key');

        $this->apiKeyRepository->expects($this->once())
            ->method('findActiveKeys')
            ->willReturn([$apiKey])
        ;

        // Even with existing data, should still call usage service because of --force
        $this->usageService->expects($this->atLeastOnce())
            ->method('getUsageForApiKey')
            ->willReturn([])
        ;

        // EntityManager flush will be called by the command

        $this->commandTester->execute([
            '--hours' => '1',
            '--force' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Syncing usage for key: Test Key', $this->commandTester->getDisplay());
    }

    public function testExecuteWithUsageData(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setName('Test Key');

        // Don't create a real ApiKeyUsage here, let the mock handle it

        // Mock usage result with prompt tokens
        $metricValue = new UsageMetricValue(1640995200, 100); // Jan 1, 2022 00:00:00
        $metricItem = new UsageMetricItem(
            [['Key' => 'EndpointId', 'Value' => 'endpoint-123']],
            [$metricValue]
        );
        $usageResult = new UsageResult('CompletionTokens', [$metricItem]);

        $this->apiKeyRepository->expects($this->once())
            ->method('findActiveKeys')
            ->willReturn([$apiKey])
        ;

        $this->apiKeyUsageRepository->expects($this->atLeastOnce())
            ->method('findByApiKeyAndDateRange')
            ->willReturn([])
        ;

        // Create a single usage object to be reused
        $createdUsageObjects = [];
        $this->apiKeyUsageRepository->expects($this->atLeastOnce())
            ->method('findOrCreateByKeyAndHour')
            ->willReturnCallback(function ($apiKeyParam, $hour, $endpointId = null) use (&$createdUsageObjects) {
                $this->assertInstanceOf(\Tourze\VolcanoArkApiBundle\Entity\ApiKey::class, $apiKeyParam);
                $this->assertInstanceOf(\DateTimeImmutable::class, $hour);
                // Create a unique key for this combination
                $endpointKey = $endpointId ?? 'null';
                $this->assertIsString($endpointKey);
                $key = $apiKeyParam->getName() . '_' . $hour->format('Y-m-d H:00:00') . '_' . $endpointKey;

                // Return the same object if already created
                if (isset($createdUsageObjects[$key])) {
                    return $createdUsageObjects[$key];
                }

                $usage = new ApiKeyUsage();
                $reflection = new \ReflectionClass($usage);
                $apiKeyProperty = $reflection->getProperty('apiKey');
                $apiKeyProperty->setAccessible(true);
                $apiKeyProperty->setValue($usage, $apiKeyParam);

                $usageHourProperty = $reflection->getProperty('usageHour');
                $usageHourProperty->setAccessible(true);
                $usageHourProperty->setValue($usage, $hour);

                if ($endpointId) {
                    $endpointIdProperty = $reflection->getProperty('endpointId');
                    $endpointIdProperty->setAccessible(true);
                    $endpointIdProperty->setValue($usage, $endpointId);
                }

                // Store this object for future calls
                $createdUsageObjects[$key] = $usage;

                return $usage;
            })
        ;

        $this->usageService->expects($this->atLeastOnce())
            ->method('getUsageForApiKey')
            ->willReturn([$usageResult])
        ;

        // EntityManager persist will be called by the command

        // EntityManager flush will be called by the command

        $this->commandTester->execute([
            '--hours' => '1',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Usage sync completed successfully', $this->commandTester->getDisplay());
    }

    public function testExecuteWithApiException(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setName('Test Key');

        $this->apiKeyRepository->expects($this->once())
            ->method('findActiveKeys')
            ->willReturn([$apiKey])
        ;

        $this->apiKeyUsageRepository->expects($this->atLeastOnce())
            ->method('findByApiKeyAndDateRange')
            ->willReturn([])
        ;

        $this->usageService->expects($this->atLeastOnce())
            ->method('getUsageForApiKey')
            ->willThrowException(new \RuntimeException('API Error'))
        ;

        // EntityManager flush will be called by the command

        $this->commandTester->execute([
            '--hours' => '1',
        ]);

        // The command catches exceptions and continues, so it should still succeed
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Failed to fetch usage', $output);
        $this->assertStringContainsString('API Error', $output);
    }

    public function testExecuteWithNonExistentApiKeyName(): void
    {
        $this->apiKeyRepository->expects($this->once())
            ->method('findByName')
            ->with('NonExistent')
            ->willReturn(null)
        ;

        $this->commandTester->execute([
            '--key-name' => 'NonExistent',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('No API keys found to sync', $this->commandTester->getDisplay());
    }

    public function testExecuteWithPromptTokensData(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setName('Test Key');

        $usage = new ApiKeyUsage();
        $usage->setApiKey($apiKey);

        // Mock usage result with prompt tokens
        $metricValue = new UsageMetricValue(1640995200, 50); // Jan 1, 2022 00:00:00
        $metricItem = new UsageMetricItem(
            [['Key' => 'EndpointId', 'Value' => 'endpoint-123']],
            [$metricValue]
        );
        $usageResult = new UsageResult('PromptTokens', [$metricItem]);

        $this->apiKeyRepository->expects($this->once())
            ->method('findActiveKeys')
            ->willReturn([$apiKey])
        ;

        $this->apiKeyUsageRepository->expects($this->atLeastOnce())
            ->method('findByApiKeyAndDateRange')
            ->willReturn([])
        ;

        // Create a single usage object to be reused for prompt tokens test
        $createdUsageObjects = [];
        $this->apiKeyUsageRepository->expects($this->atLeastOnce())
            ->method('findOrCreateByKeyAndHour')
            ->willReturnCallback(function ($apiKeyParam, $hour, $endpointId = null) use (&$createdUsageObjects) {
                $this->assertInstanceOf(\Tourze\VolcanoArkApiBundle\Entity\ApiKey::class, $apiKeyParam);
                $this->assertInstanceOf(\DateTimeImmutable::class, $hour);
                // Create a unique key for this combination
                $endpointKey = $endpointId ?? 'null';
                $this->assertIsString($endpointKey);
                $key = $apiKeyParam->getName() . '_' . $hour->format('Y-m-d H:00:00') . '_' . $endpointKey;

                // Return the same object if already created
                if (isset($createdUsageObjects[$key])) {
                    return $createdUsageObjects[$key];
                }

                $usage = new ApiKeyUsage();
                $reflection = new \ReflectionClass($usage);
                $apiKeyProperty = $reflection->getProperty('apiKey');
                $apiKeyProperty->setAccessible(true);
                $apiKeyProperty->setValue($usage, $apiKeyParam);

                $usageHourProperty = $reflection->getProperty('usageHour');
                $usageHourProperty->setAccessible(true);
                $usageHourProperty->setValue($usage, $hour);

                if ($endpointId) {
                    $endpointIdProperty = $reflection->getProperty('endpointId');
                    $endpointIdProperty->setAccessible(true);
                    $endpointIdProperty->setValue($usage, $endpointId);
                }

                // Store this object for future calls
                $createdUsageObjects[$key] = $usage;

                return $usage;
            })
        ;

        $this->usageService->expects($this->atLeastOnce())
            ->method('getUsageForApiKey')
            ->willReturn([$usageResult])
        ;

        // EntityManager persist will be called by the command

        // EntityManager flush will be called by the command

        $this->commandTester->execute([
            '--hours' => '1',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Usage sync completed successfully', $this->commandTester->getDisplay());
    }

    public function testOptionStartDate(): void
    {
        // 测试--start-date选项
        $this->apiKeyRepository->expects($this->once())
            ->method('findActiveKeys')
            ->willReturn([])
        ;

        $this->commandTester->execute([
            '--start-date' => '2024-01-01 00:00:00',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('2024-01-01 00:00:00', $this->commandTester->getDisplay());
    }

    public function testOptionEndDate(): void
    {
        // 测试--end-date选项
        $this->apiKeyRepository->expects($this->once())
            ->method('findActiveKeys')
            ->willReturn([])
        ;

        $this->commandTester->execute([
            '--end-date' => '2024-01-01 23:59:59',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('2024-01-01 23:59:59', $this->commandTester->getDisplay());
    }

    public function testOptionKeyName(): void
    {
        // 测试--key-name选项
        $apiKey = new ApiKey();
        $apiKey->setName('TestKey');

        $this->apiKeyRepository->expects($this->once())
            ->method('findByName')
            ->with('TestKey')
            ->willReturn($apiKey)
        ;

        $this->apiKeyUsageRepository->expects($this->atLeastOnce())
            ->method('findByApiKeyAndDateRange')
            ->willReturn([])
        ;

        $this->usageService->expects($this->atLeastOnce())
            ->method('getUsageForApiKey')
            ->willReturn([])
        ;

        $this->commandTester->execute([
            '--key-name' => 'TestKey',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Syncing usage for key: TestKey', $this->commandTester->getDisplay());
    }

    public function testOptionHours(): void
    {
        // 测试--hours选项
        $this->apiKeyRepository->expects($this->once())
            ->method('findActiveKeys')
            ->willReturn([])
        ;

        $this->commandTester->execute([
            '--hours' => '12',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        // Test that the command respects the hours option by checking the time period
        $this->assertStringContainsString('Period:', $this->commandTester->getDisplay());
    }

    public function testOptionForce(): void
    {
        // 测试--force选项
        $apiKey = new ApiKey();
        $apiKey->setName('TestKey');

        $this->apiKeyRepository->expects($this->once())
            ->method('findActiveKeys')
            ->willReturn([$apiKey])
        ;

        $this->usageService->expects($this->atLeastOnce())
            ->method('getUsageForApiKey')
            ->willReturn([])
        ;

        $this->commandTester->execute([
            '--force' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Usage sync completed successfully', $this->commandTester->getDisplay());
    }
}
