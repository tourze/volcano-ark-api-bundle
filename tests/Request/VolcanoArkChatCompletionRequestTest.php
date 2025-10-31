<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OpenAiContracts\DTO\ChatMessage;
use Tourze\VolcanoArkApiBundle\Request\VolcanoArkChatCompletionRequest;

/**
 * @internal
 */
#[CoversClass(VolcanoArkChatCompletionRequest::class)]
class VolcanoArkChatCompletionRequestTest extends TestCase
{
    public function testCreate(): void
    {
        $messages = [
            new ChatMessage('user', 'Hello, how are you?'),
            new ChatMessage('assistant', 'I am doing well, thank you!'),
        ];

        $request = VolcanoArkChatCompletionRequest::create($messages, 'volcano-model');

        $this->assertInstanceOf(VolcanoArkChatCompletionRequest::class, $request);
    }

    public function testCreateWithOptions(): void
    {
        $messages = [
            new ChatMessage('user', 'What is the weather like?'),
        ];

        $options = [
            'temperature' => 0.7,
            'max_tokens' => 150,
            'top_p' => 0.9,
        ];

        $request = VolcanoArkChatCompletionRequest::create($messages, 'volcano-model');
        // Set options
        $request->setTemperature($options['temperature']);
        $request->setMaxTokens($options['max_tokens']);
        $request->setTopP($options['top_p']);

        $this->assertInstanceOf(VolcanoArkChatCompletionRequest::class, $request);
    }

    public function testGetMessages(): void
    {
        $messages = [
            new ChatMessage('user', 'Test message'),
        ];

        $request = VolcanoArkChatCompletionRequest::create($messages, 'test-model');

        $this->assertEquals($messages, $request->getMessages());
        $this->assertCount(1, $request->getMessages());
        $this->assertEquals('user', $request->getMessages()[0]->getRole());
        $this->assertEquals('Test message', $request->getMessages()[0]->getContent());
    }

    public function testGetModel(): void
    {
        $messages = [
            new ChatMessage('user', 'Test'),
        ];

        $request = VolcanoArkChatCompletionRequest::create($messages, 'custom-volcano-model');

        $this->assertEquals('custom-volcano-model', $request->getModel());
    }

    public function testWithMultipleMessages(): void
    {
        $messages = [
            new ChatMessage('system', 'You are a helpful assistant.'),
            new ChatMessage('user', 'What is 2+2?'),
            new ChatMessage('assistant', '2+2 equals 4.'),
            new ChatMessage('user', 'Thank you!'),
        ];

        $request = VolcanoArkChatCompletionRequest::create($messages, 'volcano-math-model');

        $this->assertEquals($messages, $request->getMessages());
        $this->assertCount(4, $request->getMessages());
        $this->assertEquals('volcano-math-model', $request->getModel());
    }

    public function testWithEmptyMessages(): void
    {
        $messages = [];

        $request = VolcanoArkChatCompletionRequest::create($messages, 'volcano-model');

        $this->assertEquals([], $request->getMessages());
        $this->assertCount(0, $request->getMessages());
        $this->assertEquals('volcano-model', $request->getModel());
    }

    public function testMessageRoles(): void
    {
        $messages = [
            new ChatMessage('system', 'System prompt'),
            new ChatMessage('user', 'User message'),
            new ChatMessage('assistant', 'Assistant response'),
        ];

        $request = VolcanoArkChatCompletionRequest::create($messages, 'test-model');

        $retrievedMessages = $request->getMessages();

        $this->assertEquals('system', $retrievedMessages[0]->getRole());
        $this->assertEquals('user', $retrievedMessages[1]->getRole());
        $this->assertEquals('assistant', $retrievedMessages[2]->getRole());

        $this->assertEquals('System prompt', $retrievedMessages[0]->getContent());
        $this->assertEquals('User message', $retrievedMessages[1]->getContent());
        $this->assertEquals('Assistant response', $retrievedMessages[2]->getContent());
    }

    public function testToArray(): void
    {
        $messages = [
            new ChatMessage('user', 'Hello world'),
        ];

        $request = VolcanoArkChatCompletionRequest::create($messages, 'test-model');
        // Set options
        $request->setTemperature(0.7);
        $request->setMaxTokens(100);

        $array = $request->toArray();

        $this->assertEquals('test-model', $array['model']);
        $messages = $array['messages'];
        $this->assertIsArray($messages);
        $this->assertCount(1, $messages);
        $firstMessage = $messages[0];
        $this->assertIsArray($firstMessage);
        $this->assertEquals('user', $firstMessage['role']);
        $this->assertEquals('Hello world', $firstMessage['content']);
        $this->assertEquals(0.7, $array['temperature']);
        $this->assertEquals(100, $array['max_tokens']);
    }

    public function testValidate(): void
    {
        $messages = [
            new ChatMessage('user', 'Test message'),
        ];

        $request = VolcanoArkChatCompletionRequest::create($messages, 'test-model');

        // Should not throw any exception
        $request->validate();

        // Verify the request is still valid after validation
        $this->assertCount(1, $request->getMessages());
        $this->assertEquals('test-model', $request->getModel());
    }
}
