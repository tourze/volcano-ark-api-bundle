<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\VolcanoArkApiBundle\Command\ApiKeyManageCommand;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Repository\ApiKeyRepository;
use Tourze\VolcanoArkApiBundle\Service\ApiKeyService;

/**
 * @internal
 */
#[CoversClass(ApiKeyManageCommand::class)]
#[RunTestsInSeparateProcesses]
class ApiKeyManageCommandTest extends AbstractCommandTestCase
{
    private ApiKeyService $apiKeyService;
    private ApiKeyRepository $apiKeyRepository;
    private CommandTester $commandTester;

    protected function onSetUp(): void
    {
        $this->apiKeyService = self::getService(ApiKeyService::class);
        $this->apiKeyRepository = self::getService(ApiKeyRepository::class);

        $command = self::getService(ApiKeyManageCommand::class);
        $this->assertInstanceOf(ApiKeyManageCommand::class, $command);
        $this->commandTester = new CommandTester($command);
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    public function testListKeysWithNoKeys(): void
    {
        // 清理数据库中的所有API Key
        $allKeys = $this->apiKeyRepository->findAll();
        foreach ($allKeys as $key) {
            self::getEntityManager()->remove($key);
        }
        self::getEntityManager()->flush();

        // 确保数据库是空的
        $this->assertCount(0, $this->apiKeyRepository->findAll());

        $this->commandTester->execute(['action' => 'list']);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('No API keys found', $this->commandTester->getDisplay());
    }

    public function testListKeysWithExistingKeys(): void
    {
        // 创建并保存第一个 API Key
        $apiKey1 = $this->apiKeyService->createKey(
            'Test Key 1',
            'test-api-key-1',
            'test-secret-key-1',
            'cn-beijing'
        );

        // 创建并保存第二个 API Key
        $apiKey2 = $this->apiKeyService->createKey(
            'Test Key 2',
            'test-api-key-2',
            'test-secret-key-2',
            'cn-shanghai'
        );
        $this->apiKeyService->deactivateKey($apiKey2);

        $this->commandTester->execute(['action' => 'list']);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Test Key 1', $output);
        $this->assertStringContainsString('Test Key 2', $output);
        $this->assertStringContainsString('Active', $output);
        $this->assertStringContainsString('Inactive', $output);
        $this->assertStringContainsString('cn-beijing', $output);
        $this->assertStringContainsString('cn-shanghai', $output);
    }

    public function testCreateKeySuccess(): void
    {
        $this->commandTester->execute([
            'action' => 'create',
            '--name' => 'New Key',
            '--api-key' => 'test-api-key',
            '--secret-key' => 'test-secret-key',
            '--region' => 'cn-beijing',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('API key "New Key" created successfully', $this->commandTester->getDisplay());

        // 验证密钥确实被创建了
        $createdKey = $this->apiKeyService->findKeyByName('New Key');
        $this->assertNotNull($createdKey);
        $this->assertEquals('New Key', $createdKey->getName());
        $this->assertEquals('test-api-key', $createdKey->getApiKey());
        $this->assertEquals('test-secret-key', $createdKey->getSecretKey());
        $this->assertEquals('cn-beijing', $createdKey->getRegion());
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
        // 先创建一个密钥
        $apiKey = $this->apiKeyService->createKey(
            'Test Key',
            'test-api-key',
            'test-secret-key'
        );
        $this->apiKeyService->deactivateKey($apiKey);

        $this->commandTester->execute([
            'action' => 'activate',
            '--name' => 'Test Key',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('API key "Test Key" activated', $this->commandTester->getDisplay());

        // 验证密钥确实被激活了
        $activatedKey = $this->apiKeyService->findKeyByName('Test Key');
        $this->assertNotNull($activatedKey);
        $this->assertTrue($activatedKey->isActive());
    }

    public function testActivateKeyNotFound(): void
    {
        // 确保这个密钥不存在
        $this->assertNull($this->apiKeyService->findKeyByName('NonExistent'));

        $this->commandTester->execute([
            'action' => 'activate',
            '--name' => 'NonExistent',
        ]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('API key with name "NonExistent" not found', $this->commandTester->getDisplay());
    }

    public function testDeactivateKeySuccess(): void
    {
        // 先创建一个激活的密钥
        $apiKey = $this->apiKeyService->createKey(
            'Test Key',
            'test-api-key',
            'test-secret-key'
        );

        $this->commandTester->execute([
            'action' => 'deactivate',
            '--name' => 'Test Key',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('API key "Test Key" deactivated', $this->commandTester->getDisplay());

        // 验证密钥确实被停用了
        $deactivatedKey = $this->apiKeyService->findKeyByName('Test Key');
        $this->assertNotNull($deactivatedKey);
        $this->assertFalse($deactivatedKey->isActive());
    }

    public function testDeleteKeyWithConfirmation(): void
    {
        // 先创建一个密钥
        $apiKey = $this->apiKeyService->createKey(
            'Test Key',
            'test-api-key',
            'test-secret-key'
        );
        $keyId = $apiKey->getId();

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'action' => 'delete',
            '--name' => 'Test Key',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('API key "Test Key" deleted', $this->commandTester->getDisplay());

        // 验证密钥确实被删除了
        $deletedKey = $this->apiKeyRepository->find($keyId);
        $this->assertNull($deletedKey);
    }

    public function testDeleteKeyCancellation(): void
    {
        // 先创建一个密钥
        $apiKey = $this->apiKeyService->createKey(
            'Test Key',
            'test-api-key',
            'test-secret-key'
        );
        $keyId = $apiKey->getId();

        $this->commandTester->setInputs(['no']);
        $this->commandTester->execute([
            'action' => 'delete',
            '--name' => 'Test Key',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Deletion cancelled', $this->commandTester->getDisplay());

        // 验证密钥没有被删除
        $existingKey = $this->apiKeyRepository->find($keyId);
        $this->assertNotNull($existingKey);
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
        $command = self::getService(\Tourze\VolcanoArkApiBundle\Command\ApiKeyManageCommand::class);
        $definition = $command->getDefinition();

        // 验证 action 参数存在
        $this->assertTrue($definition->hasArgument('action'));
        $actionArgument = $definition->getArgument('action');
        $this->assertTrue($actionArgument->isRequired());
        $this->assertEquals('Action to perform: list, create, activate, deactivate, delete', $actionArgument->getDescription());
    }

    public function testOptionName(): void
    {
        $command = self::getService(\Tourze\VolcanoArkApiBundle\Command\ApiKeyManageCommand::class);
        $definition = $command->getDefinition();

        // 验证 --name 选项存在
        $this->assertTrue($definition->hasOption('name'));
        $nameOption = $definition->getOption('name');
        $this->assertEquals('API key name', $nameOption->getDescription());
    }

    public function testOptionApiKey(): void
    {
        $command = self::getService(\Tourze\VolcanoArkApiBundle\Command\ApiKeyManageCommand::class);
        $definition = $command->getDefinition();

        // 验证 --api-key 选项存在
        $this->assertTrue($definition->hasOption('api-key'));
        $apiKeyOption = $definition->getOption('api-key');
        $this->assertEquals('API key value', $apiKeyOption->getDescription());
    }

    public function testOptionSecretKey(): void
    {
        $command = self::getService(\Tourze\VolcanoArkApiBundle\Command\ApiKeyManageCommand::class);
        $definition = $command->getDefinition();

        // 验证 --secret-key 选项存在
        $this->assertTrue($definition->hasOption('secret-key'));
        $secretKeyOption = $definition->getOption('secret-key');
        $this->assertEquals('Secret key value', $secretKeyOption->getDescription());
    }

    public function testOptionRegion(): void
    {
        $command = self::getService(\Tourze\VolcanoArkApiBundle\Command\ApiKeyManageCommand::class);
        $definition = $command->getDefinition();

        // 验证 --region 选项存在
        $this->assertTrue($definition->hasOption('region'));
        $regionOption = $definition->getOption('region');
        $this->assertEquals('Region (default: cn-beijing)', $regionOption->getDescription());
    }
}
