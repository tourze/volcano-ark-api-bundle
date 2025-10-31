<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Request;

use Tourze\OpenAiContracts\DTO\ChatMessage;
use Tourze\OpenAiContracts\Request\AbstractChatCompletionRequest;
use Tourze\VolcanoArkApiBundle\Exception\GenericApiException;

class VolcanoArkChatCompletionRequest extends AbstractChatCompletionRequest
{
    /**
     * @param ChatMessage[] $messages
     */
    public static function create(array $messages, string $model = 'ep-20241101044227-7kjqb'): self
    {
        $request = new self();
        $request->setMessages($messages);
        $request->setModel($model);

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'model' => $this->model,
            'messages' => array_map(
                fn (ChatMessage $message) => $message->toArray(),
                $this->messages
            ),
        ];

        if (null !== $this->temperature) {
            $data['temperature'] = $this->temperature;
        }

        if (null !== $this->maxTokens) {
            $data['max_tokens'] = $this->maxTokens;
        }

        if (null !== $this->topP) {
            $data['top_p'] = $this->topP;
        }

        if (null !== $this->n) {
            $data['n'] = $this->n;
        }

        if (null !== $this->stream) {
            $data['stream'] = $this->stream;
        }

        if (null !== $this->stop) {
            $data['stop'] = $this->stop;
        }

        if (null !== $this->presencePenalty) {
            $data['presence_penalty'] = $this->presencePenalty;
        }

        if (null !== $this->frequencyPenalty) {
            $data['frequency_penalty'] = $this->frequencyPenalty;
        }

        if (null !== $this->user) {
            $data['user'] = $this->user;
        }

        return $data;
    }

    public function validate(): void
    {
        if ([] === $this->messages) {
            throw new GenericApiException('Messages array cannot be empty');
        }

        if ('' === $this->model) {
            throw new GenericApiException('Model cannot be empty');
        }
    }
}
