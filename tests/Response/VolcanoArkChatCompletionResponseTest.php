<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Response;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\VolcanoArkApiBundle\Response\VolcanoArkChatCompletionResponse;

/**
 * @internal
 */
#[CoversClass(VolcanoArkChatCompletionResponse::class)]
class VolcanoArkChatCompletionResponseTest extends TestCase
{
    public function testFromArray(): void
    {
        $data = [
            'id' => 'chatcmpl-123',
            'object' => 'chat.completion',
            'created' => 1677652288,
            'model' => 'volcano-model',
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Hello! How can I help you today?',
                    ],
                    'finish_reason' => 'stop',
                ],
            ],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 8,
                'total_tokens' => 18,
            ],
        ];

        $response = VolcanoArkChatCompletionResponse::fromArray($data);

        $this->assertEquals('chatcmpl-123', $response->getId());
        $this->assertEquals('chat.completion', $response->getObject());
        $this->assertEquals(1677652288, $response->getCreated());
        $this->assertEquals('volcano-model', $response->getModel());

        $choices = $response->getChoices();
        $this->assertCount(1, $choices);
        $this->assertEquals(0, $choices[0]->getIndex());
        $this->assertEquals('assistant', $choices[0]->getMessage()->getRole());
        $this->assertEquals('Hello! How can I help you today?', $choices[0]->getMessage()->getContent());
        $this->assertEquals('stop', $choices[0]->getFinishReason());

        $usage = $response->getUsage();
        $this->assertNotNull($usage);
        $this->assertEquals(10, $usage->getPromptTokens());
        $this->assertEquals(8, $usage->getCompletionTokens());
        $this->assertEquals(18, $usage->getTotalTokens());
    }

    public function testFromArrayWithMissingData(): void
    {
        $data = [
            'id' => 'chatcmpl-456',
            'object' => 'chat.completion',
        ];

        $response = VolcanoArkChatCompletionResponse::fromArray($data);

        $this->assertEquals('chatcmpl-456', $response->getId());
        $this->assertEquals('chat.completion', $response->getObject());
        $this->assertEquals(0, $response->getCreated());
        $this->assertEquals('', $response->getModel());
        $this->assertEquals([], $response->getChoices());
        $this->assertNull($response->getUsage());
    }

    public function testFromArrayWithMultipleChoices(): void
    {
        $data = [
            'id' => 'chatcmpl-multi',
            'choices' => [
                [
                    'index' => 0,
                    'message' => ['role' => 'assistant', 'content' => 'First choice'],
                    'finish_reason' => 'stop',
                ],
                [
                    'index' => 1,
                    'message' => ['role' => 'assistant', 'content' => 'Second choice'],
                    'finish_reason' => 'stop',
                ],
            ],
        ];

        $response = VolcanoArkChatCompletionResponse::fromArray($data);
        $choices = $response->getChoices();

        $this->assertCount(2, $choices);
        $this->assertEquals('First choice', $choices[0]->getMessage()->getContent());
        $this->assertEquals('Second choice', $choices[1]->getMessage()->getContent());
    }

    public function testGetters(): void
    {
        $data = [
            'id' => 'test-id',
            'object' => 'test-object',
            'created' => 1234567890,
            'model' => 'test-model',
            'choices' => [],
        ];

        $response = VolcanoArkChatCompletionResponse::fromArray($data);

        $this->assertEquals('test-id', $response->getId());
        $this->assertEquals('test-object', $response->getObject());
        $this->assertEquals(1234567890, $response->getCreated());
        $this->assertEquals('test-model', $response->getModel());
        $this->assertIsArray($response->getChoices());
    }

    public function testJsonSerialize(): void
    {
        $data = [
            'id' => 'chatcmpl-test',
            'object' => 'chat.completion',
            'created' => 1677652288,
            'model' => 'volcano-model',
            'choices' => [
                [
                    'index' => 0,
                    'message' => ['role' => 'assistant', 'content' => 'Test response'],
                    'finish_reason' => 'stop',
                ],
            ],
            'usage' => [
                'prompt_tokens' => 5,
                'completion_tokens' => 3,
                'total_tokens' => 8,
            ],
        ];

        $response = VolcanoArkChatCompletionResponse::fromArray($data);
        $jsonData = $response->jsonSerialize();

        $this->assertIsArray($jsonData);
        $this->assertEquals('chatcmpl-test', $jsonData['id']);
        $this->assertEquals('chat.completion', $jsonData['object']);
        $this->assertEquals(1677652288, $jsonData['created']);
        $this->assertEquals('volcano-model', $jsonData['model']);
        $this->assertArrayHasKey('choices', $jsonData);
        $this->assertArrayHasKey('usage', $jsonData);
    }

    public function testToArray(): void
    {
        $data = [
            'id' => 'chatcmpl-array',
            'object' => 'chat.completion',
            'created' => 1677652290,
            'model' => 'test-model',
            'choices' => [
                [
                    'index' => 0,
                    'message' => ['role' => 'assistant', 'content' => 'Array test'],
                    'finish_reason' => 'stop',
                ],
            ],
        ];

        $response = VolcanoArkChatCompletionResponse::fromArray($data);
        $arrayData = $response->toArray();

        $this->assertIsArray($arrayData);
        $this->assertEquals('chatcmpl-array', $arrayData['id']);
        $this->assertEquals('chat.completion', $arrayData['object']);
        $this->assertEquals(1677652290, $arrayData['created']);
        $this->assertEquals('test-model', $arrayData['model']);
        $this->assertArrayHasKey('choices', $arrayData);
    }
}
