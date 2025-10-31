<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Request;

use HttpClientBundle\Request\ApiRequest;
use Tourze\VolcanoArkApiBundle\DTO\AuditLogFilter;

/**
 * @https://www.volcengine.com/docs/82379/1289652
 */
class ListAuditLogsRequest extends ApiRequest
{
    public function __construct(
        private readonly string $resourceId,
        private readonly string $resourceType,
        private readonly AuditLogFilter $filter,
        private readonly ?string $projectName = null,
        private readonly int $pageNumber = 1,
        private readonly int $pageSize = 10,
        private readonly string $sortBy = 'Timestamp',
        private readonly string $sortOrder = 'Desc',
    ) {
    }

    public function getRequestPath(): string
    {
        return 'ListAuditLogs';
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
            'Action' => 'ListAuditLogs',
            'Version' => '2024-01-01',
            'ResourceId' => $this->resourceId,
            'ResourceType' => $this->resourceType,
            'Filter' => $this->filter->toArray(),
            'PageNumber' => $this->pageNumber,
            'PageSize' => $this->pageSize,
            'SortBy' => $this->sortBy,
            'SortOrder' => $this->sortOrder,
        ];

        if (null !== $this->projectName) {
            $params['ProjectName'] = $this->projectName;
        }

        return $params;
    }
}
