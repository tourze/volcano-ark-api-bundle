<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\DTO;

class AuditLogFilter
{
    public function __construct(
        public readonly ?string $logType = null,
        public readonly ?string $riskLevel = null,
        public readonly ?string $startTime = null,
        public readonly ?string $endTime = null,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $result = [];

        if (null !== $this->logType) {
            $result['LogType'] = $this->logType;
        }

        if (null !== $this->riskLevel) {
            $result['RiskLevel'] = $this->riskLevel;
        }

        if (null !== $this->startTime) {
            $result['StartTime'] = $this->startTime;
        }

        if (null !== $this->endTime) {
            $result['EndTime'] = $this->endTime;
        }

        return $result;
    }
}
