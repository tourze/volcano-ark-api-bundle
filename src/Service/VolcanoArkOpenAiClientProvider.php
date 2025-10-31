<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Tourze\OpenAiContracts\Client\OpenAiCompatibleClientInterface;
use Tourze\OpenAiContracts\Provider\OpenAiClientProviderInterface;
use Tourze\VolcanoArkApiBundle\Repository\ApiKeyRepository;

#[WithMonologChannel(channel: 'volcano-ark-api')]
#[AutoconfigureTag(name: self::TAG_NAME)]
readonly class VolcanoArkOpenAiClientProvider implements OpenAiClientProviderInterface
{
    public function __construct(
        private ApiKeyRepository $apiKeyRepository,
        private VolcanoArkOpenAiClientFactory $clientFactory,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     * @return iterable<OpenAiCompatibleClientInterface>
     */
    public function fetchOpenAiClientWithConfig(array $config = []): iterable
    {
        $apiKeys = $this->apiKeyRepository->findActiveAndValidKeys();

        foreach ($apiKeys as $apiKey) {
            try {
                $client = $this->clientFactory->createClient($apiKey, $config);
                yield $client;
            } catch (\Exception $e) {
                $this->logger->error('Error while creating Volcano Ark client with config', [
                    'error' => $e->getMessage(),
                    'key' => $apiKey->getName(),
                    'config' => $config,
                ]);
                continue;
            }
        }
    }

    /**
     * @return OpenAiCompatibleClientInterface|null
     */
    public function getFirstAvailableClient(): ?OpenAiCompatibleClientInterface
    {
        foreach ($this->fetchOpenAiClient() as $client) {
            if ($client->isAvailable()) {
                return $client;
            }
        }

        return null;
    }

    /**
     * @return iterable<OpenAiCompatibleClientInterface>
     */
    public function fetchOpenAiClient(): iterable
    {
        $apiKeys = $this->apiKeyRepository->findActiveAndValidKeys();

        foreach ($apiKeys as $apiKey) {
            try {
                $client = $this->clientFactory->createClient($apiKey);
                yield $client;
            } catch (\Exception $e) {
                $this->logger->error('Error while creating Volcano Ark client', [
                    'error' => $e->getMessage(),
                    'key' => $apiKey->getName(),
                ]);
                continue;
            }
        }
    }

    /**
     * @param string $modelId
     * @return OpenAiCompatibleClientInterface|null
     */
    public function getClientForModel(string $modelId): ?OpenAiCompatibleClientInterface
    {
        foreach ($this->fetchOpenAiClient() as $client) {
            try {
                $models = $client->listModels();
                foreach ($models->getData() as $model) {
                    if ($model->getId() === $modelId) {
                        return $client;
                    }
                }
            } catch (\Exception $e) {
                $this->logger->warning('Error checking model availability', [
                    'model' => $modelId,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        return null;
    }

    /**
     * @return OpenAiCompatibleClientInterface|null
     */
    public function getClientByPriority(): ?OpenAiCompatibleClientInterface
    {
        $apiKeys = $this->apiKeyRepository->findByPriority();

        foreach ($apiKeys as $apiKey) {
            try {
                $client = $this->clientFactory->createClient($apiKey);
                if ($client->isAvailable()) {
                    return $client;
                }
            } catch (\Exception $e) {
                $this->logger->error('Error creating Volcano Ark client by priority', [
                    'error' => $e->getMessage(),
                    'key' => $apiKey->getName(),
                ]);
                continue;
            }
        }

        return null;
    }

    /**
     * @param string $botId
     * @param array<string, mixed> $config
     * @return OpenAiCompatibleClientInterface|null
     */
    public function getClientForBot(string $botId, array $config = []): ?OpenAiCompatibleClientInterface
    {
        $apiKeys = $this->apiKeyRepository->findActiveAndValidKeys();

        foreach ($apiKeys as $apiKey) {
            try {
                $client = $this->clientFactory->createClientForBot($botId, $apiKey, $config);
                if ($client->isAvailable()) {
                    return $client;
                }
            } catch (\Exception $e) {
                $this->logger->error('Error creating Volcano Ark bot client', [
                    'error' => $e->getMessage(),
                    'bot' => $botId,
                    'key' => $apiKey->getName(),
                ]);
                continue;
            }
        }

        return null;
    }
}
