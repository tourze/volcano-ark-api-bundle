<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\DTO;

class AuditLogResult
{
    /**
     * @param AuditLogItem[] $items
     */
    public function __construct(
        public readonly int $totalCount,
        public readonly int $pageNumber,
        public readonly int $pageSize,
        public readonly array $items,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $rawItems = $data['Items'] ?? [];
        assert(is_array($rawItems));

        /** @var AuditLogItem[] $items */
        $items = [];
        foreach ($rawItems as $item) {
            if (!is_array($item)) {
                continue;
            }
            /** @var array<string, mixed> $item */
            $items[] = AuditLogItem::fromArray($item);
        }

        $totalCount = $data['TotalCount'] ?? 0;
        assert(is_int($totalCount));

        $pageNumber = $data['PageNumber'] ?? 1;
        assert(is_int($pageNumber));

        $pageSize = $data['PageSize'] ?? 10;
        assert(is_int($pageSize));

        return new self(
            totalCount: $totalCount,
            pageNumber: $pageNumber,
            pageSize: $pageSize,
            items: $items
        );
    }
}
