<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\VolcanoArkApiBundle\Exception\GenericApiException;

/**
 * @internal
 */
#[CoversClass(GenericApiException::class)]
class GenericApiExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInheritsFromRuntimeException(): void
    {
        $exception = new GenericApiException('Test message');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testExceptionWithMessage(): void
    {
        $message = 'API Error occurred';
        $exception = new GenericApiException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'API rate limit exceeded';
        $code = 429;
        $exception = new GenericApiException($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    public function testExceptionWithMessageCodeAndPrevious(): void
    {
        $previousException = new \Exception('Previous error');
        $message = 'API timeout';
        $code = 408;
        $exception = new GenericApiException($message, $code, $previousException);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testExceptionWithEmptyMessage(): void
    {
        $exception = new GenericApiException('');

        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testExceptionWithZeroCode(): void
    {
        $exception = new GenericApiException('API Error', 0);

        $this->assertEquals('API Error', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testExceptionWithNegativeCode(): void
    {
        $exception = new GenericApiException('API Error', -1);

        $this->assertEquals('API Error', $exception->getMessage());
        $this->assertEquals(-1, $exception->getCode());
    }

    public function testExceptionIsThrowable(): void
    {
        $exception = new GenericApiException('Test exception');

        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(GenericApiException::class);
        $this->expectExceptionMessage('Test throw');
        $this->expectExceptionCode(500);

        throw new GenericApiException('Test throw', 500);
    }

    public function testExceptionWithLongMessage(): void
    {
        $longMessage = str_repeat('This is a very long error message. ', 100);
        $exception = new GenericApiException($longMessage);

        $this->assertEquals($longMessage, $exception->getMessage());
        $this->assertEquals(strlen($longMessage), strlen($exception->getMessage()));
    }

    public function testExceptionWithSpecialCharacters(): void
    {
        $messageWithSpecialChars = 'API Error: 你好 世界! @#$%^&*()';
        $exception = new GenericApiException($messageWithSpecialChars);

        $this->assertEquals($messageWithSpecialChars, $exception->getMessage());
    }

    public function testExceptionWithNewlineCharacters(): void
    {
        $messageWithNewlines = "API Error\nLine 2\nLine 3";
        $exception = new GenericApiException($messageWithNewlines);

        $this->assertEquals($messageWithNewlines, $exception->getMessage());
        $this->assertStringContainsString("\n", $exception->getMessage());
    }

    public function testExceptionToString(): void
    {
        $exception = new GenericApiException('API Error', 400);
        $stringRepresentation = (string) $exception;

        $this->assertStringContainsString('GenericApiException', $stringRepresentation);
        $this->assertStringContainsString('API Error', $stringRepresentation);
        // 检查异常消息中是否包含错误码，而不是在整个字符串表示中
        $this->assertEquals(400, $exception->getCode());
    }

    public function testExceptionTraceIsSet(): void
    {
        $exception = new GenericApiException('Test trace');
        $trace = $exception->getTrace();

        $this->assertNotEmpty($trace);
    }

    public function testExceptionFileAndLineAreSet(): void
    {
        $exception = new GenericApiException('Test file and line');

        $this->assertGreaterThan(0, $exception->getLine());
        $this->assertStringEndsWith('ApiExceptionTest.php', $exception->getFile());
    }
}
