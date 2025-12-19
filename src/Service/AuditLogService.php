<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Service;

use Tourze\VolcanoArkApiBundle\Client\VolcanoArkApiClient;
use Tourze\VolcanoArkApiBundle\DTO\AuditLogFilter;
use Tourze\VolcanoArkApiBundle\DTO\AuditLogResult;
use Tourze\VolcanoArkApiBundle\Request\ListAuditLogsRequest;

readonly final class AuditLogService
{
    public function __construct(
        private VolcanoArkApiClient $apiClient,
    ) {
    }

    public function listAuditLogs(
        string $resourceId,
        string $resourceType,
        AuditLogFilter $filter,
        ?string $projectName = null,
        int $pageNumber = 1,
        int $pageSize = 10,
        string $sortBy = 'Timestamp',
        string $sortOrder = 'Desc',
    ): AuditLogResult {
        $request = new ListAuditLogsRequest(
            resourceId: $resourceId,
            resourceType: $resourceType,
            filter: $filter,
            projectName: $projectName,
            pageNumber: $pageNumber,
            pageSize: $pageSize,
            sortBy: $sortBy,
            sortOrder: $sortOrder
        );

        $response = $this->apiClient->request($request);

        // 类型守卫已在 VolcanoArkApiClient::formatResponse() 中完成
        // $response 现在保证是 array<string, mixed>
        return AuditLogResult::fromArray($response);
    }
}
