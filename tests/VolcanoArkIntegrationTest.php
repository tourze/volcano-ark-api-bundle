<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OpenAiContracts\DTO\ChatMessage;
use Tourze\OpenAiContracts\Enum\Role;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Entity\ApiKeyUsage;
use Tourze\VolcanoArkApiBundle\Entity\AuditLog;
use Tourze\VolcanoArkApiBundle\Repository\ApiKeyRepository;
use Tourze\VolcanoArkApiBundle\Repository\ApiKeyUsageRepository;
use Tourze\VolcanoArkApiBundle\Repository\AuditLogRepository;
use Tourze\VolcanoArkApiBundle\Request\VolcanoArkChatCompletionRequest;
use Tourze\VolcanoArkApiBundle\Service\ApiKeyService;
use Tourze\VolcanoArkApiBundle\Service\VolcanoArkOpenAiClient;
use Tourze\VolcanoArkApiBundle\VolcanoArkApiBundle;

/**
 * 火山方舟 API Bundle 集成测试
 *
 * @internal
 */
#[CoversClass(VolcanoArkApiBundle::class)]
#[RunTestsInSeparateProcesses]
#[Group('integration')]
class VolcanoArkIntegrationTest extends AbstractIntegrationTestCase
{
    private ApiKeyService $apiKeyService;

    private ApiKeyRepository $apiKeyRepository;

    private ApiKeyUsageRepository $apiKeyUsageRepository;

    private AuditLogRepository $auditLogRepository;

    private VolcanoArkOpenAiClient $openAiClient;

    protected function onSetUp(): void
    {
        $this->apiKeyService = self::getService(ApiKeyService::class);
        $this->apiKeyRepository = self::getService(ApiKeyRepository::class);
        $this->apiKeyUsageRepository = self::getService(ApiKeyUsageRepository::class);
        $this->auditLogRepository = self::getService(AuditLogRepository::class);
        $this->openAiClient = self::getService(VolcanoArkOpenAiClient::class);
    }

    public function testApiKeyManagement(): void
    {
        // 创建 API Key
        $apiKey = $this->apiKeyService->createKey(
            'integration-test-key',
            'test-api-key-12345',
            'test-secret-key-67890',
            'cn-beijing'
        );

        $this->assertInstanceOf(ApiKey::class, $apiKey);
        $this->assertEquals('integration-test-key', $apiKey->getName());
        $this->assertEquals('test-api-key-12345', $apiKey->getApiKey());
        $this->assertEquals('test-secret-key-67890', $apiKey->getSecretKey());
        $this->assertEquals('cn-beijing', $apiKey->getRegion());
        // 新创建的 key 默认是激活状态

        // 激活 API Key
        $this->apiKeyService->activateKey($apiKey);
        $this->assertTrue($apiKey->isActive());

        // 查询验证
        $foundKey = $this->apiKeyRepository->findOneBy(['name' => 'integration-test-key']);
        $this->assertNotNull($foundKey);
        $this->assertTrue($foundKey->isActive());

        // 停用 API Key
        $this->apiKeyService->deactivateKey($apiKey);
        $this->assertFalse($apiKey->isActive());

        // 测试按名称查找 API Key
        $foundByName = $this->apiKeyService->findKeyByName('integration-test-key');
        $this->assertNotNull($foundByName);
        $this->assertEquals($apiKey->getId(), $foundByName->getId());

        // 测试密钥轮换
        $rotatedKey = $this->apiKeyService->rotateKey();
        $this->assertInstanceOf(ApiKey::class, $rotatedKey);

        // 测试删除 API Key
        $keyId = $apiKey->getId();
        $this->apiKeyService->deleteKey($apiKey);

        // 验证密钥已被删除
        $deletedKey = $this->apiKeyRepository->find($keyId);
        $this->assertNull($deletedKey);
    }

    public function testApiKeyUsageRecording(): void
    {
        // 创建测试 API Key
        $apiKey = new ApiKey();
        $apiKey->setName('usage-test-key');
        $apiKey->setApiKey('usage-test-api-key');
        $apiKey->setSecretKey('usage-test-secret');
        $apiKey->setRegion('cn-beijing');
        $apiKey->setIsActive(true);

        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->flush();

        // 创建使用量记录
        $usage = new ApiKeyUsage();
        $usage->setApiKey($apiKey);
        $usage->setUsageHour(new \DateTimeImmutable('2025-01-01 10:00:00'));
        $usage->setEndpointId('ep-test-endpoint');
        $usage->setPromptTokens(50);
        $usage->setCompletionTokens(30);
        $usage->setTotalTokens(80);
        $usage->setRequestCount(1);
        $usage->setEstimatedCost('0.02');
        $usage->setMetadata(['test' => true]);

        self::getEntityManager()->persist($usage);
        self::getEntityManager()->flush();

        // 验证记录
        $this->assertNotNull($usage->getId());
        $this->assertEquals(80, $usage->getTotalTokens());
        $this->assertEquals(1, $usage->getRequestCount());

        // 查询验证
        $savedUsage = $this->apiKeyUsageRepository->find($usage->getId());
        $this->assertNotNull($savedUsage);
        $this->assertEquals('ep-test-endpoint', $savedUsage->getEndpointId());
    }

    public function testAuditLogRecording(): void
    {
        // 创建测试 API Key
        $apiKey = new ApiKey();
        $apiKey->setName('audit-test-key');
        $apiKey->setApiKey('audit-test-api-key');
        $apiKey->setSecretKey('audit-test-secret');
        $apiKey->setRegion('cn-beijing');

        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->flush();

        // 创建审计日志
        $auditLog = new AuditLog();
        $auditLog->setApiKey($apiKey);
        $auditLog->setAction('chat_completion');
        $auditLog->setDescription('测试聊天补全');
        $auditLog->setRequestPath('/chat/completions');
        $auditLog->setRequestMethod('POST');
        $auditLog->setClientIp('127.0.0.1');
        $auditLog->setUserAgent('PHPUnit/Test');
        $auditLog->setStatusCode(200);
        $auditLog->setResponseTime(1500);
        $auditLog->setIsSuccess(true);
        $auditLog->setRequestData(['model' => 'test-model', 'messages' => []]);
        $auditLog->setResponseData(['choices' => []]);
        $auditLog->setMetadata(['test_mode' => true]);

        self::getEntityManager()->persist($auditLog);
        self::getEntityManager()->flush();

        // 验证记录
        $this->assertNotNull($auditLog->getId());
        $this->assertEquals('chat_completion', $auditLog->getAction());
        $this->assertTrue($auditLog->isSuccess());
        $this->assertEquals(1500, $auditLog->getResponseTime());

        // 查询验证
        $savedLog = $this->auditLogRepository->find($auditLog->getId());
        $this->assertNotNull($savedLog);
        $this->assertEquals('POST', $savedLog->getRequestMethod());
    }

    public function testOpenAiClientConfiguration(): void
    {
        // 测试基础配置
        $this->assertEquals('VolcanoArk', $this->openAiClient->getName());
        $this->assertEquals('https://ark.cn-beijing.volces.com/api/v3/', $this->openAiClient->getBaseUrl());
        $this->assertFalse($this->openAiClient->isBotMode());

        // 测试区域设置
        $this->openAiClient->setRegion('cn-shanghai');
        $this->assertEquals('https://ark.cn-shanghai.volces.com/api/v3/', $this->openAiClient->getBaseUrl());

        // 测试 Bot 模式（设置后会更新 URL）
        $this->openAiClient->setBotMode(true);
        $this->assertTrue($this->openAiClient->isBotMode());
        // setBotMode 会根据当前区域更新 URL
        $this->assertStringEndsWith('/bots/', $this->openAiClient->getBaseUrl());

        // 测试自定义名称
        $this->openAiClient->setName('CustomClient');
        $this->assertEquals('CustomClient', $this->openAiClient->getName());
    }

    public function testChatCompletionRequest(): void
    {
        // 创建聊天消息
        $messages = [
            new ChatMessage(Role::USER, '这是一个测试消息'),
        ];

        // 创建请求对象
        $request = VolcanoArkChatCompletionRequest::create($messages, 'test-endpoint');
        $request->setMaxTokens(100);
        $request->setTemperature(0.7);

        // 验证请求对象
        $this->assertEquals('test-endpoint', $request->getModel());
        $this->assertEquals(100, $request->getMaxTokens());
        $this->assertEquals(0.7, $request->getTemperature());
        $this->assertCount(1, $request->getMessages());

        // 验证转换为数组
        $requestArray = $request->toArray();
        $this->assertArrayHasKey('model', $requestArray);
        $this->assertArrayHasKey('messages', $requestArray);
        $this->assertArrayHasKey('max_tokens', $requestArray);
        $this->assertArrayHasKey('temperature', $requestArray);

        $this->assertEquals('test-endpoint', $requestArray['model']);
        $this->assertEquals(100, $requestArray['max_tokens']);
        $this->assertEquals(0.7, $requestArray['temperature']);
    }

    public function testRepositoryQueries(): void
    {
        // 测试 API Key Repository
        $activeKeys = $this->apiKeyRepository->findBy(['isActive' => true]);

        // 测试使用量统计
        $totalUsages = $this->apiKeyUsageRepository->count([]);

        // 测试审计日志查询
        $recentLogs = $this->auditLogRepository->findBy(
            [],
            ['createTime' => 'DESC'],
            10
        );
        $this->assertLessThanOrEqual(10, count($recentLogs));
    }

    public function testEntityValidation(): void
    {
        // 测试 API Key 实体验证
        $apiKey = new ApiKey();
        $apiKey->setName('validation-test');
        $apiKey->setApiKey('validation-api-key');
        $apiKey->setSecretKey('validation-secret');
        $apiKey->setRegion('cn-beijing');

        $this->assertEquals('validation-test', $apiKey->getName());
        $this->assertTrue($apiKey->isActive()); // 默认值是激活状态
        $this->assertEquals(0, $apiKey->getUsageCount()); // 默认值

        // 测试使用计数增加
        $apiKey->incrementUsageCount();
        $this->assertEquals(1, $apiKey->getUsageCount());

        // 测试字符串转换
        $stringRepresentation = (string) $apiKey;
        $this->assertNotEmpty($stringRepresentation);
    }

    // 专门的方法测试以满足PHPStan @CoversClass要求

    public function testCreateKey(): void
    {
        $apiKey = $this->apiKeyService->createKey(
            'test-create-key',
            'create-api-key-123',
            'create-secret-key-456',
            'cn-shanghai'
        );

        $this->assertInstanceOf(ApiKey::class, $apiKey);
        $this->assertEquals('test-create-key', $apiKey->getName());
        $this->assertEquals('create-api-key-123', $apiKey->getApiKey());
        $this->assertEquals('create-secret-key-456', $apiKey->getSecretKey());
        $this->assertEquals('cn-shanghai', $apiKey->getRegion());
    }

    public function testActivateKey(): void
    {
        $apiKey = $this->apiKeyService->createKey(
            'test-activate-key',
            'activate-api-key-123',
            'activate-secret-key-456'
        );

        $apiKey->setIsActive(false); // 先设置为非激活状态
        $this->apiKeyService->activateKey($apiKey);
        $this->assertTrue($apiKey->isActive());
    }

    public function testDeactivateKey(): void
    {
        $apiKey = $this->apiKeyService->createKey(
            'test-deactivate-key',
            'deactivate-api-key-123',
            'deactivate-secret-key-456'
        );

        $this->assertTrue($apiKey->isActive()); // 默认激活
        $this->apiKeyService->deactivateKey($apiKey);
        $this->assertFalse($apiKey->isActive());
    }

    public function testFindKeyByName(): void
    {
        // 先创建一个密钥
        $originalKey = $this->apiKeyService->createKey(
            'test-find-key',
            'find-api-key-123',
            'find-secret-key-456'
        );

        // 通过名称查找
        $foundKey = $this->apiKeyService->findKeyByName('test-find-key');
        $this->assertNotNull($foundKey);
        $this->assertEquals($originalKey->getId(), $foundKey->getId());
        $this->assertEquals('test-find-key', $foundKey->getName());
    }

    public function testRotateKey(): void
    {
        // 测试密钥轮换功能
        $rotatedKey = $this->apiKeyService->rotateKey();
        $this->assertInstanceOf(ApiKey::class, $rotatedKey);
    }

    public function testDeleteKey(): void
    {
        // 创建一个测试密钥
        $apiKey = $this->apiKeyService->createKey(
            'test-delete-key',
            'delete-api-key-123',
            'delete-secret-key-456'
        );

        $keyId = $apiKey->getId();
        $this->assertNotNull($keyId);

        // 删除密钥
        $this->apiKeyService->deleteKey($apiKey);

        // 验证密钥已被删除
        $deletedKey = $this->apiKeyRepository->find($keyId);
        $this->assertNull($deletedKey);
    }
}
