<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Service;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\OpenAiContracts\Client\AbstractOpenAiClient;
use Tourze\OpenAiContracts\Response\BalanceResponseInterface;
use Tourze\OpenAiContracts\Response\ChatCompletionResponseInterface;
use Tourze\OpenAiContracts\Response\ModelListResponseInterface;
use Tourze\VolcanoArkApiBundle\Exception\ApiException;
use Tourze\VolcanoArkApiBundle\Exception\GenericApiException;
use Tourze\VolcanoArkApiBundle\Response\VolcanoArkBalanceResponse;
use Tourze\VolcanoArkApiBundle\Response\VolcanoArkChatCompletionResponse;
use Tourze\VolcanoArkApiBundle\Response\VolcanoArkModelListResponse;

class VolcanoArkOpenAiClient extends AbstractOpenAiClient
{
    private string $baseUrl = 'https://ark.cn-beijing.volces.com/api/v3/';

    private string $name = 'VolcanoArk';

    private bool $isBotMode = false;

    private ?string $endpointId = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly int $maxRetries = 3,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = rtrim($baseUrl, '/') . '/';
    }

    public function isBotMode(): bool
    {
        return $this->isBotMode;
    }

    public function setBotMode(bool $botMode): void
    {
        $this->isBotMode = $botMode;
        if ($botMode) {
            $this->baseUrl = 'https://ark.cn-beijing.volces.com/api/v3/bots/';
        } else {
            $this->baseUrl = 'https://ark.cn-beijing.volces.com/api/v3/';
        }
    }

    public function setRegion(string $region): void
    {
        $this->baseUrl = sprintf('https://ark.%s.volces.com/api/v3/', $region);
        if ($this->isBotMode) {
            $this->baseUrl .= 'bots/';
        }
    }

    public function getEndpointId(): ?string
    {
        return $this->endpointId;
    }

    public function setEndpointId(?string $endpointId): void
    {
        $this->endpointId = $endpointId;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function doRequest(string $endpoint, string $method, array $data = []): array
    {
        $requestOptions = $this->buildRequestOptions($method, $data);

        $lastException = null;
        for ($attempt = 0; $attempt < $this->maxRetries; ++$attempt) {
            try {
                return $this->performSingleRequest($method, $endpoint, $requestOptions);
            } catch (TransportExceptionInterface $e) {
                $lastException = $e;
                if ($attempt < $this->maxRetries - 1) {
                    $this->waitBeforeRetry($attempt);
                }
            }
        }

        throw new GenericApiException(sprintf('Request failed after %d attempts: %s', $this->maxRetries, $lastException?->getMessage() ?? 'Unknown error'), 0, $lastException);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function buildRequestOptions(string $method, array $data): array
    {
        $headers = [];
        $options = [];

        $authResult = $this->applyAuthentication($headers, $options);
        $headers = $authResult['headers'];
        $options = $authResult['options'];

        $requestOptions = ['headers' => $headers];

        if ('GET' === $method && [] !== $data) {
            $requestOptions['query'] = $data;
        } elseif ([] !== $data) {
            $requestOptions['json'] = $data;
        }

        return array_merge($requestOptions, $options);
    }

    /**
     * @param array<string, mixed> $requestOptions
     * @return array<string, mixed>
     */
    private function performSingleRequest(string $method, string $endpoint, array $requestOptions): array
    {
        // 如果 endpoint 不是完整 URL，则拼接 baseUrl
        if (!str_starts_with($endpoint, 'http')) {
            $endpoint = $this->baseUrl . ltrim($endpoint, '/');
        }

        $response = $this->httpClient->request($method, $endpoint, $requestOptions);
        $statusCode = $response->getStatusCode();
        $content = $response->getContent(false);

        if (200 <= $statusCode && $statusCode < 300) {
            return $this->decodeSuccessResponse($content);
        }

        throw $this->createErrorException($statusCode, $content);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeSuccessResponse(string $content): array
    {
        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            return [];
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    private function createErrorException(int $statusCode, string $content): ApiException
    {
        $errorData = json_decode($content, true);

        if (!is_array($errorData)) {
            return new GenericApiException(sprintf('HTTP %d: %s', $statusCode, $content));
        }

        /** @var array<string, mixed> $errorData */
        if (isset($errorData['error'])) {
            return new GenericApiException($this->parseError($errorData));
        }

        return new GenericApiException(sprintf('HTTP %d: %s', $statusCode, $content));
    }

    private function waitBeforeRetry(int $attempt): void
    {
        usleep((int) (pow(2, $attempt) * 1000000));
    }

    /**
     * @param array<string, mixed> $response
     */
    protected function parseError(array $response): string
    {
        if (isset($response['error']) && is_array($response['error'])) {
            $error = $response['error'];
            if (isset($error['message']) && is_string($error['message'])) {
                return $error['message'];
            }
        }

        if (isset($response['error']) && is_string($response['error'])) {
            return $response['error'];
        }

        if (isset($response['message']) && is_string($response['message'])) {
            return $response['message'];
        }

        $encoded = json_encode($response);

        return false !== $encoded ? $encoded : 'Failed to parse error response';
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function createChatCompletionResponse(array $data): ChatCompletionResponseInterface
    {
        return new VolcanoArkChatCompletionResponse($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function createModelListResponse(array $data): ModelListResponseInterface
    {
        return new VolcanoArkModelListResponse($data);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @phpstan-ignore-next-line 线程安全：仅创建响应对象，不涉及实际余额操作
     */
    protected function createBalanceResponse(array $data): BalanceResponseInterface
    {
        // 线程安全：每次调用都创建新的响应实例，无共享状态
        // 此方法仅用于创建响应对象，不涉及实际的余额变更操作
        return new VolcanoArkBalanceResponse($data);
    }

    /**
     * 覆盖父类的listModels方法，当API调用失败时返回端点ID作为模型ID
     */
    public function listModels(): ModelListResponseInterface
    {
        try {
            return parent::listModels();
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();

            // 当API调用失败时，如果有端点ID，则将其作为模型返回
            if (null !== $this->endpointId) {
                $modelData = [
                    'data' => [
                        [
                            'id' => $this->endpointId,
                            'object' => 'model',
                            'created' => time(),
                            'owned_by' => 'volcano-ark',
                        ],
                    ],
                ];

                return $this->createModelListResponse($modelData);
            }

            // 如果没有端点ID，则抛出原异常
            throw $e;
        }
    }
}
