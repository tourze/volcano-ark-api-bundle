<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Response;

use Tourze\OpenAiContracts\DTO\Model;
use Tourze\OpenAiContracts\Response\ModelListResponseInterface;
use Tourze\VolcanoArkApiBundle\Exception\UnexpectedResponseException;

final class VolcanoArkModelListResponse implements ModelListResponseInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(private readonly array $data)
    {
        $this->assertPayload($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self($data);
    }

    public function getId(): ?string
    {
        $value = $this->data['id'] ?? null;

        return is_string($value) ? $value : null;
    }

    public function getObject(): string
    {
        $value = $this->data['object'] ?? 'list';

        return is_string($value) ? $value : 'list';
    }

    public function getCreated(): ?int
    {
        $value = $this->data['created'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @return Model[]
     */
    public function getData(): array
    {
        /** @var list<array<string, mixed>> $dataList */
        $dataList = $this->data['data'] ?? [];

        $models = [];
        foreach ($dataList as $modelData) {
            if (!is_array($modelData)) {
                continue;
            }

            $models[] = new Model(
                $this->readStringFromArray($modelData, 'id', ''),
                $this->readStringFromArray($modelData, 'object', 'model'),
                $this->readIntFromArray($modelData, 'created', 0),
                $this->readStringFromArray($modelData, 'owned_by', '')
            );
        }

        return $models;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    public function jsonSerialize(): mixed
    {
        return $this->data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertPayload(array $data): void
    {
        if (isset($data['data']) && !is_array($data['data'])) {
            throw new UnexpectedResponseException('data field must be array');
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function readStringFromArray(array $data, string $key, string $default): string
    {
        $value = $data[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function readIntFromArray(array $data, string $key, int $default): int
    {
        $value = $data[$key] ?? $default;

        return is_numeric($value) ? (int) $value : $default;
    }
}
