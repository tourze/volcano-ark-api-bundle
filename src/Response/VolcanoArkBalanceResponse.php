<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Response;

use Tourze\OpenAiContracts\DTO\Balance;
use Tourze\OpenAiContracts\Response\BalanceResponseInterface;
use Tourze\VolcanoArkApiBundle\Exception\UnexpectedResponseException;

final class VolcanoArkBalanceResponse implements BalanceResponseInterface
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
        $value = $this->data['object'] ?? 'balance';

        return is_string($value) ? $value : 'balance';
    }

    public function getCreated(): ?int
    {
        $value = $this->data['created'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    public function getBalance(): Balance
    {
        $totalBalance = $this->readFloat('total_balance', 'total_granted', 0.0);
        $usedBalance = $this->readFloat('used_balance', 'total_used', 0.0);
        $remainingBalance = $this->readFloat('remaining_balance', 'total_available', $totalBalance - $usedBalance);
        $currency = $this->readString('currency', 'CNY');

        return new Balance(
            $totalBalance,
            $usedBalance,
            $remainingBalance,
            $currency
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = $this->jsonSerialize();
        assert(is_array($result));

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): mixed
    {
        $balance = $this->getBalance();

        return [
            'object' => $this->getObject(),
            'balance' => [
                'totalAmount' => $balance->getTotalAmount(),
                'usedAmount' => $balance->getUsedAmount(),
                'remainingAmount' => $balance->getRemainingAmount(),
                'currency' => $balance->getCurrency(),
            ],
        ] + $this->data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertPayload(array $data): void
    {
        if (isset($data['currency']) && !is_string($data['currency'])) {
            throw new UnexpectedResponseException('currency must be string');
        }
        foreach (['total_balance', 'total_granted', 'used_balance', 'total_used', 'remaining_balance', 'total_available'] as $key) {
            if (isset($data[$key]) && !is_numeric($data[$key])) {
                throw new UnexpectedResponseException(sprintf('%s must be numeric', $key));
            }
        }
    }

    private function readFloat(string $primaryKey, string $fallbackKey, float $default): float
    {
        $value = $this->data[$primaryKey] ?? $this->data[$fallbackKey] ?? $default;

        return is_numeric($value) ? (float) $value : $default;
    }

    private function readString(string $key, string $default): string
    {
        $value = $this->data[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }
}
