<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\DTO;

class UsageResult
{
    /**
     * @param UsageMetricItem[] $metricItems
     */
    public function __construct(
        public readonly string $name,
        public readonly array $metricItems,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $rawMetricItems = $data['MetricItems'] ?? [];
        assert(is_array($rawMetricItems));

        /** @var UsageMetricItem[] $metricItems */
        $metricItems = [];
        foreach ($rawMetricItems as $item) {
            if (!is_array($item)) {
                continue;
            }
            /** @var array<string, mixed> $item */
            $metricItems[] = UsageMetricItem::fromArray($item);
        }

        $name = $data['Name'] ?? '';
        assert(is_string($name));

        return new self(
            name: $name,
            metricItems: $metricItems
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $metricItems = [];
        foreach ($this->metricItems as $item) {
            $metricItems[] = $item->toArray();
        }

        return [
            'Name' => $this->name,
            'MetricItems' => $metricItems,
        ];
    }
}
