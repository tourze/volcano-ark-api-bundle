<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Request;

use HttpClientBundle\Request\ApiRequest;

/**
 * @see https://www.volcengine.com/docs/82379/1390292
 */
class GetUsageRequest extends ApiRequest
{
    /**
     * @param string[] $scenes
     * @param string[] $endpointIds
     */
    public function __construct(
        private readonly int $startTime,
        private readonly int $endTime,
        private readonly int $interval,
        private readonly ?string $batchJobId = null,
        private readonly array $scenes = [],
        private readonly ?string $projectName = null,
        private readonly array $endpointIds = [],
    ) {
    }

    public function getRequestPath(): string
    {
        return 'GetUsage';
    }

    public function getRequestMethod(): string
    {
        return 'POST';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRequestOptions(): ?array
    {
        $params = [
            'Action' => 'GetUsage',
            'Version' => '2024-01-01',
            'StartTime' => $this->startTime,
            'EndTime' => $this->endTime,
            'Interval' => $this->interval,
        ];

        if (null !== $this->batchJobId) {
            $params['BatchJobId'] = $this->batchJobId;
        }

        if ([] !== $this->scenes) {
            $params['Scenes'] = $this->scenes;
        }

        if (null !== $this->projectName) {
            $params['ProjectName'] = $this->projectName;
        }

        if ([] !== $this->endpointIds) {
            $params['EndpointIds'] = $this->endpointIds;
        }

        return $params;
    }
}
