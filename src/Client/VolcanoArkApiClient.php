<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Client;

use HttpClientBundle\Client\ApiClient;
use HttpClientBundle\Request\RequestInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Exception\GenericApiException;
use Tourze\VolcanoArkApiBundle\Exception\UnexpectedResponseException;
use Tourze\VolcanoArkApiBundle\Service\ApiKeyService;

#[WithMonologChannel(channel: 'volcano_ark_api')]
class VolcanoArkApiClient extends ApiClient
{
    private const API_ENDPOINT = 'https://open.volcengineapi.com';
    private const API_VERSION = '2024-01-01';
    private const API_SERVICE = 'ark';

    private ?ApiKey $currentApiKey = null;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly HttpClientInterface $httpClient,
        private readonly LockFactory $lockFactory,
        private readonly CacheInterface $cache,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AsyncInsertService $asyncInsertService,
        private readonly ApiKeyService $apiKeyService,
    ) {
    }

    private function getApiKey(): ApiKey
    {
        if (null === $this->currentApiKey) {
            $this->currentApiKey = $this->apiKeyService->getCurrentKey();
        }

        return $this->currentApiKey;
    }

    public function rotateApiKey(): void
    {
        $this->currentApiKey = $this->apiKeyService->rotateKey();
    }

    public function getCurrentApiKey(): ?ApiKey
    {
        return $this->currentApiKey;
    }

    public function setCurrentApiKey(ApiKey $apiKey): void
    {
        $this->currentApiKey = $apiKey;
    }

    /**
     * @return array<string, mixed>
     */
    public function request(RequestInterface $request): array
    {
        $result = parent::request($request);

        // 类型守卫：确保父类返回的是数组
        if (!is_array($result)) {
            throw new UnexpectedResponseException('Request result must be an array');
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    public function getBaseUrl(): string
    {
        return self::API_ENDPOINT;
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    protected function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }

    protected function getLockFactory(): LockFactory
    {
        return $this->lockFactory;
    }

    protected function getCache(): CacheInterface
    {
        return $this->cache;
    }

    protected function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    protected function getAsyncInsertService(): AsyncInsertService
    {
        return $this->asyncInsertService;
    }

    protected function getRequestUrl(RequestInterface $request): string
    {
        $path = $request->getRequestPath();

        if (str_starts_with($path, '/')) {
            return self::API_ENDPOINT . $path;
        }

        $action = $path;

        return sprintf('%s/?Action=%s&Version=%s', self::API_ENDPOINT, $action, self::API_VERSION);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function getRequestOptions(RequestInterface $request): ?array
    {
        $options = $request->getRequestOptions() ?? [];

        $now = gmdate('Ymd\THis\Z');
        $headers = [
            'Host' => parse_url(self::API_ENDPOINT, PHP_URL_HOST),
            'Content-Type' => 'application/json; charset=UTF-8',
            'X-Date' => $now,
        ];

        if (Request::METHOD_POST === $this->getRequestMethod($request)) {
            $body = json_encode($options, JSON_THROW_ON_ERROR);
            $headers['X-Content-Sha256'] = hash('sha256', $body);
            $headers['Authorization'] = $this->generateSignature(
                Request::METHOD_POST,
                $request->getRequestPath(),
                $headers,
                $body
            );

            return [
                'headers' => $headers,
                'body' => $body,
            ];
        }

        $headers['Authorization'] = $this->generateSignature(
            Request::METHOD_GET,
            $request->getRequestPath(),
            $headers,
            ''
        );

        return [
            'headers' => $headers,
        ];
    }

    protected function getRequestMethod(RequestInterface $request): string
    {
        return $request->getRequestMethod() ?? Request::METHOD_POST;
    }

    /**
     * @param array<string, string> $headers
     */
    private function generateSignature(string $method, string $uri, array $headers, string $body = ''): string
    {
        $canonicalRequest = $this->buildCanonicalRequest($method, $uri, $headers, $body);
        $credentialScope = $this->buildCredentialScope($headers['X-Date']);
        $stringToSign = $this->buildStringToSign($canonicalRequest, $credentialScope, $headers['X-Date']);

        $signingKey = $this->deriveSigningKey($headers['X-Date']);
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);

        $apiKey = $this->getApiKey();

        return sprintf(
            'HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            $apiKey->getApiKey(),
            $credentialScope,
            $this->getSignedHeaders($headers),
            $signature
        );
    }

    /**
     * @param array<string, string> $headers
     */
    private function buildCanonicalRequest(string $method, string $uri, array $headers, string $body): string
    {
        $canonicalHeaders = $this->buildCanonicalHeaders($headers);
        $signedHeaders = $this->getSignedHeaders($headers);
        $hashedPayload = hash('sha256', $body);

        return implode("\n", [
            strtoupper($method),
            $uri,
            '',
            $canonicalHeaders,
            '',
            $signedHeaders,
            $hashedPayload,
        ]);
    }

    /**
     * @param array<string, string> $headers
     */
    private function buildCanonicalHeaders(array $headers): string
    {
        $canonical = [];
        foreach ($headers as $key => $value) {
            $canonical[strtolower($key)] = trim($value);
        }
        ksort($canonical);

        $result = [];
        foreach ($canonical as $key => $value) {
            $result[] = $key . ':' . $value;
        }

        return implode("\n", $result);
    }

    /**
     * @param array<string, string> $headers
     */
    private function getSignedHeaders(array $headers): string
    {
        $keys = array_map('strtolower', array_keys($headers));
        sort($keys);

        return implode(';', $keys);
    }

    private function buildCredentialScope(string $date): string
    {
        $apiKey = $this->getApiKey();
        $dateOnly = substr($date, 0, 8);

        return sprintf('%s/%s/%s/request', $dateOnly, $apiKey->getRegion(), self::API_SERVICE);
    }

    private function buildStringToSign(string $canonicalRequest, string $credentialScope, string $date): string
    {
        return implode("\n", [
            'HMAC-SHA256',
            $date,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);
    }

    private function deriveSigningKey(string $date): string
    {
        $apiKey = $this->getApiKey();
        $dateOnly = substr($date, 0, 8);
        $kDate = hash_hmac('sha256', $dateOnly, $apiKey->getSecretKey(), true);
        $kRegion = hash_hmac('sha256', $apiKey->getRegion(), $kDate, true);
        $kService = hash_hmac('sha256', self::API_SERVICE, $kRegion, true);

        return hash_hmac('sha256', 'request', $kService, true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatResponse(RequestInterface $request, ResponseInterface $response): array
    {
        $data = $response->toArray();

        // toArray() 总是返回数组，移除冗余检查
        // 类型守卫：检查错误响应结构
        if (isset($data['ResponseMetadata']) && is_array($data['ResponseMetadata']) && isset($data['ResponseMetadata']['Error'])) {
            $this->handleErrorResponse($data['ResponseMetadata']['Error']);
        }

        $result = $data['Result'] ?? $data;

        // 类型守卫：确保结果是数组
        if (!is_array($result)) {
            throw new UnexpectedResponseException('API response result must be an array');
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    private function handleErrorResponse(mixed $error): never
    {
        // 类型守卫：确保 $error 是数组类型
        if (!is_array($error)) {
            throw new GenericApiException('API Error: Invalid error response format');
        }

        // 类型守卫：确保错误代码和消息是字符串类型
        $errorCode = isset($error['Code']) && is_string($error['Code']) ? $error['Code'] : 'UnknownError';
        $errorMessage = isset($error['Message']) && is_string($error['Message']) ? $error['Message'] : 'Unknown error';

        throw new GenericApiException(sprintf('API Error [%s]: %s', $errorCode, $errorMessage));
    }
}
