<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Service;

use Tourze\VolcanoArkApiBundle\Client\VolcanoArkApiClient;
use Tourze\VolcanoArkApiBundle\DTO\UsageResult;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Exception\UnexpectedResponseException;
use Tourze\VolcanoArkApiBundle\Request\GetUsageRequest;

readonly final class UsageService
{
    public function __construct(
        private VolcanoArkApiClient $apiClient,
    ) {
    }

    /**
     * @param string[] $scenes
     * @param string[] $endpointIds
     * @return UsageResult[]
     */
    public function getUsage(
        int $startTime,
        int $endTime,
        int $interval,
        ?string $batchJobId = null,
        array $scenes = [],
        ?string $projectName = null,
        array $endpointIds = [],
    ): array {
        $request = new GetUsageRequest(
            startTime: $startTime,
            endTime: $endTime,
            interval: $interval,
            batchJobId: $batchJobId,
            scenes: $scenes,
            projectName: $projectName,
            endpointIds: $endpointIds
        );

        $response = $this->apiClient->request($request);

        // 类型守卫：确保 UsageResults 存在且为数组
        if (!isset($response['UsageResults']) || !is_array($response['UsageResults'])) {
            throw new UnexpectedResponseException('Response must contain UsageResults array');
        }

        $results = [];
        foreach ($response['UsageResults'] as $result) {
            if (!is_array($result)) {
                throw new UnexpectedResponseException('Each usage result must be an array');
            }
            /** @var array<string, mixed> $result */
            $results[] = UsageResult::fromArray($result);
        }

        return $results;
    }

    /**
     * 获取指定 API key 的使用量
     *
     * @param string[] $scenes
     * @param string[] $endpointIds
     * @return UsageResult[]
     */
    public function getUsageForApiKey(
        ApiKey $apiKey,
        int $startTime,
        int $endTime,
        int $interval,
        ?string $batchJobId = null,
        array $scenes = [],
        ?string $projectName = null,
        array $endpointIds = [],
    ): array {
        $originalApiKey = $this->apiClient->getCurrentApiKey();

        try {
            $this->apiClient->setCurrentApiKey($apiKey);

            return $this->getUsage(
                $startTime,
                $endTime,
                $interval,
                $batchJobId,
                $scenes,
                $projectName,
                $endpointIds
            );
        } finally {
            if (null !== $originalApiKey) {
                $this->apiClient->setCurrentApiKey($originalApiKey);
            }
        }
    }
}
