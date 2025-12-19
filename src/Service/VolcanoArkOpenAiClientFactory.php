<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\OpenAiContracts\Authentication\AuthenticationStrategyInterface;
use Tourze\OpenAiContracts\Client\AbstractOpenAiClient;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Exception\GenericApiException;

final class VolcanoArkOpenAiClientFactory
{
    private HttpClientInterface $httpClient;

    public function __construct()
    {
        $this->httpClient = HttpClient::create();
    }

    /**
     * @param array<string, mixed> $config
     */
    public function createClient(ApiKey $apiKey, array $config = []): AbstractOpenAiClient
    {
        if (!$apiKey->isActive()) {
            throw new GenericApiException(sprintf('API key "%s" is not active', $apiKey->getName()));
        }

        $region = $this->extractStringConfig($config, 'region', $apiKey->getRegion());
        $botMode = $this->extractBoolConfig($config, 'bot_mode', false);
        $maxRetries = $this->extractIntConfig($config, 'max_retries', 3);

        $baseUri = $this->buildBaseUrl($region, $botMode);
        $httpClient = $this->buildHttpClient($baseUri, $config);

        $client = new VolcanoArkOpenAiClient($httpClient, $maxRetries);
        $this->configureClient($client, $apiKey, $region, $botMode, $baseUri);
        $this->applyAuthenticationStrategy($client, $config);

        return $client;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function extractStringConfig(array $config, string $key, string $default): string
    {
        $value = $config[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function extractBoolConfig(array $config, string $key, bool $default): bool
    {
        $value = $config[$key] ?? $default;

        return is_bool($value) ? $value : $default;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function extractIntConfig(array $config, string $key, int $default): int
    {
        $value = $config[$key] ?? $default;

        return is_int($value) ? $value : $default;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function buildHttpClient(string $baseUri, array $config): HttpClientInterface
    {
        $httpClientConfig = [
            'base_uri' => $baseUri,
            'timeout' => $config['timeout'] ?? 30,
            'max_redirects' => 0,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];

        foreach (['proxy', 'verify_peer', 'verify_host'] as $key) {
            if (isset($config[$key])) {
                $httpClientConfig[$key] = $config[$key];
            }
        }

        return $this->httpClient->withOptions($httpClientConfig);
    }

    private function configureClient(
        VolcanoArkOpenAiClient $client,
        ApiKey $apiKey,
        string $region,
        bool $botMode,
        string $baseUri,
    ): void {
        $client->setName($apiKey->getName());
        $client->setBaseUrl($baseUri);
        $client->setApiKey($apiKey->getApiKey());
        $client->setBotMode($botMode);
        $client->setRegion($region);

        if ('' !== $apiKey->getSecretKey()) {
            $client->setEndpointId($apiKey->getSecretKey());
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function applyAuthenticationStrategy(VolcanoArkOpenAiClient $client, array $config): void
    {
        if (!isset($config['authentication_strategy'])) {
            return;
        }

        $authStrategy = $config['authentication_strategy'];
        if ($authStrategy instanceof AuthenticationStrategyInterface) {
            $client->setAuthenticationStrategy($authStrategy);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    public function createClientForModel(string $modelId, ApiKey $apiKey, array $config = []): AbstractOpenAiClient
    {
        $config['model'] = $modelId;

        return $this->createClient($apiKey, $config);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function createClientForBot(string $botId, ApiKey $apiKey, array $config = []): AbstractOpenAiClient
    {
        $config['bot_mode'] = true;
        $config['model'] = $botId;

        return $this->createClient($apiKey, $config);
    }

    private function buildBaseUrl(string $region, bool $botMode): string
    {
        $baseUrl = sprintf('https://ark.%s.volces.com/api/v3/', $region);
        if ($botMode) {
            $baseUrl .= 'bots/';
        }

        return $baseUrl;
    }
}
