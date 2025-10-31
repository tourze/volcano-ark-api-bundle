<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\VolcanoArkApiBundle\Exception\ApiException;
use Tourze\VolcanoArkApiBundle\Exception\UnexpectedResponseException;

/**
 * @internal
 */
#[CoversClass(UnexpectedResponseException::class)]
class UnexpectedResponseExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInheritsFromApiException(): void
    {
        $exception = new UnexpectedResponseException('Test message');

        $this->assertInstanceOf(ApiException::class, $exception);
    }

    public function testExceptionInheritsFromRuntimeException(): void
    {
        $exception = new UnexpectedResponseException('Test message');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testExceptionWithMessage(): void
    {
        $message = 'Unexpected API response format';
        $exception = new UnexpectedResponseException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'Invalid JSON response';
        $code = 500;
        $exception = new UnexpectedResponseException($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    public function testExceptionWithMessageCodeAndPrevious(): void
    {
        $previousException = new \Exception('Parse error');
        $message = 'Response decode failed';
        $code = 502;
        $exception = new UnexpectedResponseException($message, $code, $previousException);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testExceptionIsThrowable(): void
    {
        $exception = new UnexpectedResponseException('Test exception');

        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('Unexpected response');
        $this->expectExceptionCode(503);

        throw new UnexpectedResponseException('Unexpected response', 503);
    }

    public function testExceptionToString(): void
    {
        $exception = new UnexpectedResponseException('Unexpected response', 400);
        $stringRepresentation = (string) $exception;

        $this->assertStringContainsString('UnexpectedResponseException', $stringRepresentation);
        $this->assertStringContainsString('Unexpected response', $stringRepresentation);
        $this->assertEquals(400, $exception->getCode());
    }

    public function testExceptionTraceIsSet(): void
    {
        $exception = new UnexpectedResponseException('Test trace');
        $trace = $exception->getTrace();

        $this->assertNotEmpty($trace);
    }

    public function testExceptionFileAndLineAreSet(): void
    {
        $exception = new UnexpectedResponseException('Test file and line');

        $this->assertGreaterThan(0, $exception->getLine());
        $this->assertStringEndsWith('UnexpectedResponseExceptionTest.php', $exception->getFile());
    }
}
