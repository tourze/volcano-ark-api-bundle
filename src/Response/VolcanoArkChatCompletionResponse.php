<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Response;

use Tourze\OpenAiContracts\DTO\ChatChoice;
use Tourze\OpenAiContracts\DTO\ChatMessage;
use Tourze\OpenAiContracts\DTO\Usage;
use Tourze\OpenAiContracts\Response\ChatCompletionResponseInterface;
use Tourze\VolcanoArkApiBundle\Exception\UnexpectedResponseException;

final class VolcanoArkChatCompletionResponse implements ChatCompletionResponseInterface
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
        $value = $this->data['object'] ?? 'chat.completion';

        return is_string($value) ? $value : 'chat.completion';
    }

    public function getCreated(): ?int
    {
        $value = $this->data['created'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    public function getModel(): ?string
    {
        $value = $this->data['model'] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @return ChatChoice[]
     */
    public function getChoices(): array
    {
        /** @var list<array<string, mixed>> $choicesList */
        $choicesList = $this->data['choices'] ?? [];

        $choices = [];
        foreach ($choicesList as $choiceData) {
            if (!is_array($choiceData)) {
                continue;
            }

            /** @var array<string, mixed> $messageData */
            $messageData = is_array($choiceData['message'] ?? null) ? $choiceData['message'] : [];

            $message = new ChatMessage(
                $this->readStringFromArray($messageData, 'role', 'assistant'),
                $this->readStringFromArray($messageData, 'content', '')
            );

            $choices[] = new ChatChoice(
                $this->readIntFromArray($choiceData, 'index', 0),
                $message,
                null,
                $this->readStringOrNullFromArray($choiceData, 'finish_reason')
            );
        }

        return $choices;
    }

    public function getUsage(): ?Usage
    {
        if (!isset($this->data['usage'])) {
            return null;
        }

        /** @var array<string, mixed> $usageData */
        $usageData = is_array($this->data['usage']) ? $this->data['usage'] : [];

        return new Usage(
            $this->readIntFromArray($usageData, 'prompt_tokens', 0),
            $this->readIntFromArray($usageData, 'completion_tokens', 0),
            $this->readIntFromArray($usageData, 'total_tokens', 0)
        );
    }

    public function getSystemFingerprint(): ?string
    {
        $value = $this->data['system_fingerprint'] ?? null;

        return is_string($value) ? $value : null;
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
        if (isset($data['choices']) && !is_array($data['choices'])) {
            throw new UnexpectedResponseException('choices field must be array');
        }

        if (isset($data['usage']) && !is_array($data['usage'])) {
            throw new UnexpectedResponseException('usage field must be array');
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
    private function readStringOrNullFromArray(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : null;
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
