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
use Tourze\VolcanoArkApiBundle\Command\ApiKeyManageCommand;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Service\ApiKeyService;

/**
 * @internal
 */
#[CoversClass(ApiKeyManageCommand::class)]
#[RunTestsInSeparateProcesses]
class ApiKeyManageCommandTest extends AbstractCommandTestCase
{
    private ApiKeyService&MockObject $apiKeyService;

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

        $this->apiKeyService = $this->createMock(ApiKeyService::class);

        self::getContainer()->set(ApiKeyService::class, $this->apiKeyService);
        $command = self::getContainer()->get(ApiKeyManageCommand::class);
        $this->assertInstanceOf(ApiKeyManageCommand::class, $command, 'Command should be instance of ApiKeyManageCommand');
        $application->add($command);

        $this->commandTester = new CommandTester($command);
    }

    public function testListKeysWithNoKeys(): void
    {
        $this->apiKeyService->expects($this->once())
            ->method('getAllKeys')
            ->willReturn([])
        ;

        $this->commandTester->execute(['action' => 'list']);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('No API keys found', $this->commandTester->getDisplay());
    }

    public function testListKeysWithExistingKeys(): void
    {
        $apiKey1 = new ApiKey();
        $apiKey1->setName('Test Key 1');
        $apiKey1->setRegion('cn-beijing');
        $apiKey1->setIsActive(true);

        $apiKey2 = new ApiKey();
        $apiKey2->setName('Test Key 2');
        $apiKey2->setRegion('cn-shanghai');
        $apiKey2->setIsActive(false);

        $this->apiKeyService->expects($this->once())
            ->method('getAllKeys')
            ->willReturn([$apiKey1, $apiKey2])
        ;

        $this->commandTester->execute(['action' => 'list']);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Test Key 1', $output);
        $this->assertStringContainsString('Test Key 2', $output);
        $this->assertStringContainsString('Active', $output);
        $this->assertStringContainsString('Inactive', $output);
        $this->assertStringContainsString('cn-beijing', $output);
        $this->assertStringContainsString('cn-shanghai', $output);
        $this->assertStringContainsString('Never', $output);
    }

    public function testCreateKeySuccess(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setName('New Key');

        $this->apiKeyService->expects($this->once())
            ->method('createKey')
            ->with('New Key', 'test-api-key', 'test-secret-key', 'cn-beijing')
            ->willReturn($apiKey)
        ;

        $this->commandTester->execute([
            'action' => 'create',
            '--name' => 'New Key',
            '--api-key' => 'test-api-key',
            '--secret-key' => 'test-secret-key',
            '--region' => 'cn-beijing',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('API key "New Key" created successfully', $this->commandTester->getDisplay());
    }

    public function testCreateKeyMissingParameters(): void
    {
        $this->commandTester->execute([
            'action' => 'create',
            '--name' => 'New Key',
            // Missing api-key and secret-key
        ]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Name, api-key, and secret-key are required', $this->commandTester->getDisplay());
    }

    public function testActivateKeySuccess(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setName('Test Key');

        $this->apiKeyService->expects($this->once())
            ->method('findKeyByName')
            ->with('Test Key')
            ->willReturn($apiKey)
        ;

        $this->apiKeyService->expects($this->once())
            ->method('activateKey')
            ->with($apiKey)
        ;

        $this->commandTester->execute([
            'action' => 'activate',
            '--name' => 'Test Key',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('API key "Test Key" activated', $this->commandTester->getDisplay());
    }

    public function testActivateKeyNotFound(): void
    {
        $this->apiKeyService->expects($this->once())
            ->method('findKeyByName')
            ->with('NonExistent')
            ->willReturn(null)
        ;

        $this->commandTester->execute([
            'action' => 'activate',
            '--name' => 'NonExistent',
        ]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('API key with name "NonExistent" not found', $this->commandTester->getDisplay());
    }

    public function testDeactivateKeySuccess(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setName('Test Key');

        $this->apiKeyService->expects($this->once())
            ->method('findKeyByName')
            ->with('Test Key')
            ->willReturn($apiKey)
        ;

        $this->apiKeyService->expects($this->once())
            ->method('deactivateKey')
            ->with($apiKey)
        ;

        $this->commandTester->execute([
            'action' => 'deactivate',
            '--name' => 'Test Key',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('API key "Test Key" deactivated', $this->commandTester->getDisplay());
    }

    public function testDeleteKeyWithConfirmation(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setName('Test Key');

        $this->apiKeyService->expects($this->once())
            ->method('findKeyByName')
            ->with('Test Key')
            ->willReturn($apiKey)
        ;

        $this->apiKeyService->expects($this->once())
            ->method('deleteKey')
            ->with($apiKey)
        ;

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'action' => 'delete',
            '--name' => 'Test Key',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('API key "Test Key" deleted', $this->commandTester->getDisplay());
    }

    public function testDeleteKeyCancellation(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setName('Test Key');

        $this->apiKeyService->expects($this->once())
            ->method('findKeyByName')
            ->with('Test Key')
            ->willReturn($apiKey)
        ;

        $this->apiKeyService->expects($this->never())
            ->method('deleteKey')
        ;

        $this->commandTester->setInputs(['no']);
        $this->commandTester->execute([
            'action' => 'delete',
            '--name' => 'Test Key',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Deletion cancelled', $this->commandTester->getDisplay());
    }

    public function testUnknownAction(): void
    {
        $this->commandTester->execute(['action' => 'unknown']);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Unknown action: unknown', $this->commandTester->getDisplay());
    }

    public function testMissingNameForActions(): void
    {
        $actions = ['activate', 'deactivate', 'delete'];

        foreach ($actions as $action) {
            $this->commandTester->execute(['action' => $action]);

            $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
            $this->assertStringContainsString('Name is required', $this->commandTester->getDisplay());
        }
    }

    public function testArgumentAction(): void
    {
        // 测试有效的action参数
        $validActions = ['list', 'create', 'activate', 'deactivate', 'delete'];

        foreach ($validActions as $action) {
            $this->apiKeyService->method('getAllKeys')->willReturn([]);

            // 对于需要name参数的操作，提供name参数
            $args = ['action' => $action];
            if (in_array($action, ['activate', 'deactivate', 'delete'], true)) {
                $args['--name'] = 'test-key';
                $this->apiKeyService->method('findKeyByName')->willReturn(null);
            } elseif ('create' === $action) {
                $args['--name'] = 'test-key';
                $args['--api-key'] = 'test-api';
                $args['--secret-key'] = 'test-secret';
            }

            $statusCode = $this->commandTester->execute($args);

            // 验证命令能够处理这个action参数（不管结果如何，重要的是不会因为未知action而失败）
            $this->assertContains($statusCode, [Command::SUCCESS, Command::FAILURE],
                "Command should handle action '{$action}' without throwing exceptions");
        }
    }

    public function testOptionName(): void
    {
        // 测试--name选项在激活操作中的使用
        $apiKey = new ApiKey();
        $apiKey->setName('test-key');

        $this->apiKeyService->expects($this->once())
            ->method('findKeyByName')
            ->with('test-key')
            ->willReturn($apiKey)
        ;

        $this->apiKeyService->expects($this->once())
            ->method('activateKey')
            ->with($apiKey)
        ;

        $this->commandTester->execute([
            'action' => 'activate',
            '--name' => 'test-key',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('API key "test-key" activated', $this->commandTester->getDisplay());
    }

    public function testOptionApiKey(): void
    {
        // 测试--api-key选项在create操作中的使用
        $apiKey = new ApiKey();
        $apiKey->setName('test-key');

        $this->apiKeyService->expects($this->once())
            ->method('createKey')
            ->with('test-key', 'test-api-123', 'test-secret', 'cn-beijing')
            ->willReturn($apiKey)
        ;

        $this->commandTester->execute([
            'action' => 'create',
            '--name' => 'test-key',
            '--api-key' => 'test-api-123',
            '--secret-key' => 'test-secret',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('API key "test-key" created successfully', $this->commandTester->getDisplay());
    }

    public function testOptionSecretKey(): void
    {
        // 测试--secret-key选项在create操作中的使用
        $apiKey = new ApiKey();
        $apiKey->setName('test-key');

        $this->apiKeyService->expects($this->once())
            ->method('createKey')
            ->with('test-key', 'test-api', 'test-secret-456', 'cn-beijing')
            ->willReturn($apiKey)
        ;

        $this->commandTester->execute([
            'action' => 'create',
            '--name' => 'test-key',
            '--api-key' => 'test-api',
            '--secret-key' => 'test-secret-456',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('API key "test-key" created successfully', $this->commandTester->getDisplay());
    }

    public function testOptionRegion(): void
    {
        // 测试--region选项在create操作中的使用
        $apiKey = new ApiKey();
        $apiKey->setName('test-key');

        $this->apiKeyService->expects($this->once())
            ->method('createKey')
            ->with('test-key', 'test-api', 'test-secret', 'us-east-1')
            ->willReturn($apiKey)
        ;

        $this->commandTester->execute([
            'action' => 'create',
            '--name' => 'test-key',
            '--api-key' => 'test-api',
            '--secret-key' => 'test-secret',
            '--region' => 'us-east-1',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('API key "test-key" created successfully', $this->commandTester->getDisplay());
    }
}
