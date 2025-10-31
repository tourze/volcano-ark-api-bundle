<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\DTO;

class UsageMetricItem
{
    /**
     * @param array<int, array<string, string>> $tags
     * @param UsageMetricValue[] $values
     */
    public function __construct(
        public readonly array $tags,
        public readonly array $values,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $rawValues = $data['Values'] ?? [];
        assert(is_array($rawValues));

        /** @var UsageMetricValue[] $values */
        $values = [];
        foreach ($rawValues as $value) {
            if (!is_array($value)) {
                continue;
            }
            /** @var array<string, mixed> $value */
            $values[] = UsageMetricValue::fromArray($value);
        }

        $tags = $data['Tags'] ?? [];
        assert(is_array($tags));
        /** @var array<int, array<string, string>> $tags */

        return new self(
            tags: $tags,
            values: $values
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $values = [];
        foreach ($this->values as $value) {
            $values[] = $value->toArray();
        }

        return [
            'Tags' => $this->tags,
            'Values' => $values,
        ];
    }
}
