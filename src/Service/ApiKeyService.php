<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Exception\GenericApiException;
use Tourze\VolcanoArkApiBundle\Repository\ApiKeyRepository;

#[WithMonologChannel(channel: 'volcano_ark_api')]
final class ApiKeyService
{
    private ?ApiKey $currentKey = null;

    public function __construct(
        private readonly ApiKeyRepository $apiKeyRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getCurrentKey(): ApiKey
    {
        if (null === $this->currentKey) {
            $this->currentKey = $this->selectKey();
        }

        return $this->currentKey;
    }

    private function selectKey(): ApiKey
    {
        $key = $this->apiKeyRepository->findActiveKey();

        if (null === $key) {
            $this->logger->error('No active API key found for Volcano Ark');
            throw new GenericApiException('No active API key available');
        }

        $key->incrementUsageCount();
        $this->apiKeyRepository->save($key, true);

        $this->logger->info('Selected API key', [
            'key_id' => $key->getId(),
            'key_name' => $key->getName(),
            'usage_count' => $key->getUsageCount(),
        ]);

        return $key;
    }

    public function rotateKey(): ApiKey
    {
        $this->currentKey = $this->selectKey();

        return $this->currentKey;
    }

    public function createKey(string $name, string $apiKey, string $secretKey, string $region = 'cn-beijing'): ApiKey
    {
        $key = new ApiKey();
        $key->setName($name);
        $key->setApiKey($apiKey);
        $key->setSecretKey($secretKey);
        $key->setRegion($region);
        $key->setProvider('volcano_ark');

        $this->apiKeyRepository->save($key, true);

        $this->logger->info('Created new API key', [
            'key_id' => $key->getId(),
            'key_name' => $key->getName(),
        ]);

        return $key;
    }

    public function deactivateKey(ApiKey $key): void
    {
        $key->setIsActive(false);
        $this->apiKeyRepository->save($key, true);

        $this->logger->info('Deactivated API key', [
            'key_id' => $key->getId(),
            'key_name' => $key->getName(),
        ]);
    }

    public function activateKey(ApiKey $key): void
    {
        $key->setIsActive(true);
        $this->apiKeyRepository->save($key, true);

        $this->logger->info('Activated API key', [
            'key_id' => $key->getId(),
            'key_name' => $key->getName(),
        ]);
    }

    /**
     * @return ApiKey[]
     */
    public function getAllKeys(): array
    {
        return $this->apiKeyRepository->findAll();
    }

    /**
     * @return ApiKey[]
     */
    public function getActiveKeys(): array
    {
        return $this->apiKeyRepository->findActiveKeys();
    }

    public function findKeyByName(string $name): ?ApiKey
    {
        return $this->apiKeyRepository->findByName($name);
    }

    public function deleteKey(ApiKey $key): void
    {
        $this->apiKeyRepository->remove($key);

        $this->logger->info('Deleted API key', [
            'key_id' => $key->getId(),
            'key_name' => $key->getName(),
        ]);
    }
}
