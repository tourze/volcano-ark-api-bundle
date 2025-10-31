<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\DTO;

class AuditLogItem
{
    /**
     * @param array<int, array<string, string>> $logContents
     */
    public function __construct(
        public readonly string $resourceId,
        public readonly string $resourceType,
        public readonly string $logType,
        public readonly string $logDetail,
        public readonly array $logContents,
        public readonly string $riskLevel,
        public readonly string $timestamp,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $resourceId = $data['ResourceId'] ?? '';
        assert(is_string($resourceId));

        $resourceType = $data['ResourceType'] ?? '';
        assert(is_string($resourceType));

        $logType = $data['LogType'] ?? '';
        assert(is_string($logType));

        $logDetail = $data['LogDetail'] ?? '';
        assert(is_string($logDetail));

        $logContents = $data['LogContents'] ?? [];
        assert(is_array($logContents));
        /** @var array<int, array<string, string>> $logContents */

        $riskLevel = $data['RiskLevel'] ?? '';
        assert(is_string($riskLevel));

        $timestamp = $data['Timestamp'] ?? '';
        assert(is_string($timestamp));

        return new self(
            resourceId: $resourceId,
            resourceType: $resourceType,
            logType: $logType,
            logDetail: $logDetail,
            logContents: $logContents,
            riskLevel: $riskLevel,
            timestamp: $timestamp
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ResourceId' => $this->resourceId,
            'ResourceType' => $this->resourceType,
            'LogType' => $this->logType,
            'LogDetail' => $this->logDetail,
            'LogContents' => $this->logContents,
            'RiskLevel' => $this->riskLevel,
            'Timestamp' => $this->timestamp,
        ];
    }
}
