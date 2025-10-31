<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Exception\ApiException;
use Tourze\VolcanoArkApiBundle\Service\VolcanoArkOpenAiClient;
use Tourze\VolcanoArkApiBundle\Service\VolcanoArkOpenAiClientFactory;

/**
 * @internal
 */
#[CoversClass(VolcanoArkOpenAiClientFactory::class)]
#[RunTestsInSeparateProcesses]
class VolcanoArkOpenAiClientFactoryTest extends AbstractIntegrationTestCase
{
    private VolcanoArkOpenAiClientFactory $factory;

    protected function onSetUp(): void
    {
        $this->factory = self::getService(VolcanoArkOpenAiClientFactory::class);
    }

    private function createTestApiKey(): ApiKey
    {
        $apiKey = new ApiKey();
        $apiKey->setName('test-key');
        $apiKey->setApiKey('test-api-key-value');
        $apiKey->setSecretKey('test-secret-key');
        $apiKey->setRegion('cn-beijing');
        $apiKey->setProvider('volcano_ark');
        $apiKey->setIsActive(true);

        return $apiKey;
    }

    public function testCreateClient(): void
    {
        $apiKey = $this->createTestApiKey();

        $client = $this->factory->createClient($apiKey);

        $this->assertInstanceOf(VolcanoArkOpenAiClient::class, $client);
        $this->assertEquals('test-key', $client->getName());
        $this->assertEquals('https://ark.cn-beijing.volces.com/api/v3/', $client->getBaseUrl());
        $this->assertFalse($client->isBotMode());
    }

    public function testCreateClientWithCustomRegion(): void
    {
        $apiKey = $this->createTestApiKey();
        $config = ['region' => 'cn-shanghai'];

        $client = $this->factory->createClient($apiKey, $config);

        $this->assertInstanceOf(VolcanoArkOpenAiClient::class, $client);
        $this->assertEquals('https://ark.cn-shanghai.volces.com/api/v3/', $client->getBaseUrl());
    }

    public function testCreateClientWithBotMode(): void
    {
        $apiKey = $this->createTestApiKey();
        $config = ['bot_mode' => true];

        $client = $this->factory->createClient($apiKey, $config);

        $this->assertInstanceOf(VolcanoArkOpenAiClient::class, $client);
        $this->assertEquals('https://ark.cn-beijing.volces.com/api/v3/bots/', $client->getBaseUrl());
        $this->assertTrue($client->isBotMode());
    }

    public function testCreateClientWithCustomTimeout(): void
    {
        $apiKey = $this->createTestApiKey();
        $config = ['timeout' => 60];

        $client = $this->factory->createClient($apiKey, $config);

        $this->assertInstanceOf(VolcanoArkOpenAiClient::class, $client);
    }

    public function testCreateClientWithProxy(): void
    {
        $apiKey = $this->createTestApiKey();
        $config = ['proxy' => 'http://proxy.example.com:8080'];

        $client = $this->factory->createClient($apiKey, $config);

        $this->assertInstanceOf(VolcanoArkOpenAiClient::class, $client);
    }

    public function testCreateClientWithSslOptions(): void
    {
        $apiKey = $this->createTestApiKey();
        $config = [
            'verify_peer' => false,
            'verify_host' => false,
        ];

        $client = $this->factory->createClient($apiKey, $config);

        $this->assertInstanceOf(VolcanoArkOpenAiClient::class, $client);
    }

    public function testCreateClientThrowsExceptionForInactiveKey(): void
    {
        $inactiveKey = new ApiKey();
        $inactiveKey->setName('inactive-key');
        $inactiveKey->setApiKey('test-api');
        $inactiveKey->setSecretKey('test-secret');
        $inactiveKey->setRegion('cn-beijing');
        $inactiveKey->setProvider('volcano_ark');
        $inactiveKey->setIsActive(false); // 设置为非活跃状态

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('API key "inactive-key" is not active');

        $this->factory->createClient($inactiveKey);
    }

    public function testCreateClientForModel(): void
    {
        $apiKey = $this->createTestApiKey();

        $client = $this->factory->createClientForModel('model-123', $apiKey);

        $this->assertInstanceOf(VolcanoArkOpenAiClient::class, $client);
    }

    public function testCreateClientForBot(): void
    {
        $apiKey = $this->createTestApiKey();

        $client = $this->factory->createClientForBot('bot-123', $apiKey);

        $this->assertInstanceOf(VolcanoArkOpenAiClient::class, $client);
        $this->assertTrue($client->isBotMode());
        $this->assertEquals('https://ark.cn-beijing.volces.com/api/v3/bots/', $client->getBaseUrl());
    }

    public function testCreateClientWithApiKey(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setApiKey('test-api-key');
        $apiKey->setSecretKey('test-secret-key');
        $apiKey->setIsActive(true);
        $apiKey->setRegion('cn-beijing');
        $apiKey->setProvider('volcano_ark');

        $client = $this->factory->createClient($apiKey);

        $this->assertInstanceOf(VolcanoArkOpenAiClient::class, $client);
        $this->assertEquals('https://ark.cn-beijing.volces.com/api/v3/', $client->getBaseUrl());
    }

    public function testCreateClientWithApiKeyAndBotMode(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setApiKey('test-api-key');
        $apiKey->setSecretKey('test-secret-key');
        $apiKey->setIsActive(true);
        $apiKey->setRegion('cn-shanghai');
        $apiKey->setProvider('volcano_ark');

        $config = [
            'bot_mode' => true,
        ];

        $client = $this->factory->createClient($apiKey, $config);

        $this->assertInstanceOf(VolcanoArkOpenAiClient::class, $client);
        $this->assertEquals('https://ark.cn-shanghai.volces.com/api/v3/bots/', $client->getBaseUrl());
        $this->assertTrue($client->isBotMode());
    }

    public function testCreateClientFromApiKeyString(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setApiKey('test-api-key-string');
        $apiKey->setSecretKey('test-secret-key');
        $apiKey->setName('Test Key');
        $apiKey->setRegion('cn-beijing');
        $apiKey->setProvider('volcano_ark');
        $apiKey->setIsActive(true);

        $client = $this->factory->createClient($apiKey);

        $this->assertInstanceOf(VolcanoArkOpenAiClient::class, $client);
        $this->assertEquals('https://ark.cn-beijing.volces.com/api/v3/', $client->getBaseUrl());
        $this->assertFalse($client->isBotMode());
    }

    public function testCreateClientFromApiKeyStringWithCustomConfig(): void
    {
        $config = [
            'region' => 'cn-shanghai',
            'bot_mode' => true,
            'timeout' => 60,
            'max_retries' => 5,
        ];

        $apiKey = new ApiKey();
        $apiKey->setApiKey('test-api-key-string');
        $apiKey->setSecretKey('test-secret-key');
        $apiKey->setName('Test Key');
        $apiKey->setRegion('cn-shanghai');
        $apiKey->setProvider('volcano_ark');
        $apiKey->setIsActive(true);

        $client = $this->factory->createClient($apiKey, $config);

        $this->assertInstanceOf(VolcanoArkOpenAiClient::class, $client);
        $this->assertEquals('https://ark.cn-shanghai.volces.com/api/v3/bots/', $client->getBaseUrl());
        $this->assertTrue($client->isBotMode());
    }
}
