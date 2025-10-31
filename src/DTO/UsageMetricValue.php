<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\DTO;

class UsageMetricValue
{
    public function __construct(
        public readonly int $timestamp,
        public readonly float $value,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $rawTimestamp = $data['Timestamp'] ?? 0;
        // Allow int or numeric string
        if (is_string($rawTimestamp)) {
            $rawTimestamp = (int) $rawTimestamp;
        }
        assert(is_int($rawTimestamp));

        $rawValue = $data['Value'] ?? 0;
        // Allow int, float, or numeric string
        if (is_string($rawValue)) {
            $rawValue = (float) $rawValue;
        }
        assert(is_int($rawValue) || is_float($rawValue));

        return new self(
            timestamp: $rawTimestamp,
            value: (float) $rawValue
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'Timestamp' => $this->timestamp,
            'Value' => $this->value,
        ];
    }
}
