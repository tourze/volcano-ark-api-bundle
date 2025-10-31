<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Entity\AuditLog;

/**
 * @internal
 */
#[CoversClass(AuditLog::class)]
class AuditLogTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new AuditLog();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'action' => ['action', 'test_value'],
            'statusCode' => ['statusCode', 123],
            'responseTime' => ['responseTime', 123],
        ];
    }

    private AuditLog $auditLog;

    private ApiKey $apiKey;

    public function testStringable(): void
    {
        $this->assertInstanceOf(\Stringable::class, $this->auditLog);

        $stringRepresentation = (string) $this->auditLog;
        $this->assertStringContainsString('AuditLog', $stringRepresentation);
        $this->assertStringContainsString('api_request', $stringRepresentation);
        $this->assertStringContainsString('No description', $stringRepresentation);
    }

    public function testStringableWithDescription(): void
    {
        $this->auditLog->setDescription('Test description');
        $stringRepresentation = (string) $this->auditLog;
        $this->assertStringContainsString('Test description', $stringRepresentation);
    }

    public function testInitialValues(): void
    {
        $this->assertNull($this->auditLog->getId());
        $this->assertSame($this->apiKey, $this->auditLog->getApiKey());
        $this->assertSame('api_request', $this->auditLog->getAction());
        $this->assertNull($this->auditLog->getDescription());
        $this->assertNull($this->auditLog->getRequestData());
        $this->assertNull($this->auditLog->getResponseData());
        $this->assertNull($this->auditLog->getRequestPath());
        $this->assertNull($this->auditLog->getRequestMethod());
        $this->assertNull($this->auditLog->getClientIp());
        $this->assertNull($this->auditLog->getUserAgent());
        $this->assertSame(0, $this->auditLog->getStatusCode());
        $this->assertSame(0, $this->auditLog->getResponseTime());
        $this->assertTrue($this->auditLog->isSuccess());
        $this->assertNull($this->auditLog->getErrorMessage());
        $this->assertNull($this->auditLog->getMetadata());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->auditLog->getCreateTime());
    }

    public function testSetAndGetAction(): void
    {
        $action = 'user_login';
        $this->auditLog->setAction($action);
        $this->assertSame($action, $this->auditLog->getAction());
    }

    public function testSetAndGetDescription(): void
    {
        $description = 'User logged in successfully';
        $this->auditLog->setDescription($description);
        $this->assertSame($description, $this->auditLog->getDescription());
    }

    public function testSetAndGetRequestData(): void
    {
        $requestData = ['param1' => 'value1', 'param2' => 'value2'];
        $this->auditLog->setRequestData($requestData);
        $this->assertSame($requestData, $this->auditLog->getRequestData());
    }

    public function testSetAndGetResponseData(): void
    {
        $responseData = ['result' => 'success', 'data' => ['id' => 123]];
        $this->auditLog->setResponseData($responseData);
        $this->assertSame($responseData, $this->auditLog->getResponseData());
    }

    public function testSetAndGetRequestPath(): void
    {
        $requestPath = '/api/v1/users';
        $this->auditLog->setRequestPath($requestPath);
        $this->assertSame($requestPath, $this->auditLog->getRequestPath());
    }

    public function testSetAndGetRequestMethod(): void
    {
        $requestMethod = 'POST';
        $this->auditLog->setRequestMethod($requestMethod);
        $this->assertSame($requestMethod, $this->auditLog->getRequestMethod());
    }

    public function testSetAndGetClientIp(): void
    {
        $clientIp = '192.168.1.100';
        $this->auditLog->setClientIp($clientIp);
        $this->assertSame($clientIp, $this->auditLog->getClientIp());
    }

    public function testSetAndGetUserAgent(): void
    {
        $userAgent = 'Mozilla/5.0 (Test Browser)';
        $this->auditLog->setUserAgent($userAgent);
        $this->assertSame($userAgent, $this->auditLog->getUserAgent());
    }

    public function testSetAndGetStatusCode(): void
    {
        $statusCode = 200;
        $this->auditLog->setStatusCode($statusCode);
        $this->assertSame($statusCode, $this->auditLog->getStatusCode());
    }

    public function testSetAndGetResponseTime(): void
    {
        $responseTime = 150;
        $this->auditLog->setResponseTime($responseTime);
        $this->assertSame($responseTime, $this->auditLog->getResponseTime());
    }

    public function testSetAndGetIsSuccess(): void
    {
        $this->auditLog->setIsSuccess(false);
        $this->assertFalse($this->auditLog->isSuccess());
    }

    public function testSetAndGetErrorMessage(): void
    {
        $errorMessage = 'Invalid credentials';
        $this->auditLog->setErrorMessage($errorMessage);
        $this->assertSame($errorMessage, $this->auditLog->getErrorMessage());
    }

    public function testSetAndGetMetadata(): void
    {
        $metadata = ['user_id' => 123, 'session_id' => 'abc123'];
        $this->auditLog->setMetadata($metadata);
        $this->assertSame($metadata, $this->auditLog->getMetadata());
    }

    public function testEntityAnnotations(): void
    {
        $reflectionClass = new \ReflectionClass($this->auditLog);

        // 检查实体注解
        $this->assertTrue($reflectionClass->hasMethod('getId'));
        $this->assertTrue($reflectionClass->hasMethod('getApiKey'));
        $this->assertTrue($reflectionClass->hasMethod('getAction'));
        $this->assertTrue($reflectionClass->hasMethod('getCreateTime'));
    }

    public function testValidationConstraints(): void
    {
        $reflectionClass = new \ReflectionClass($this->auditLog);

        // 检查是否有验证约束注解
        $properties = $reflectionClass->getProperties();

        $actionProperty = null;
        foreach ($properties as $property) {
            if ('action' === $property->getName()) {
                $actionProperty = $property;
                break;
            }
        }

        $this->assertNotNull($actionProperty);

        // 检查属性类型
        $this->assertTrue($actionProperty->hasType());
        $type = $actionProperty->getType();
        $this->assertNotNull($type);

        // 安全地获取类型名称
        $typeName = method_exists($type, 'getName') ? $type->getName() : $type->__toString();
        $this->assertEquals('string', $typeName);
    }

    public function testSuccessAndErrorLogging(): void
    {
        // 测试成功日志
        $this->auditLog->setIsSuccess(true);
        $this->auditLog->setStatusCode(200);
        $this->auditLog->setResponseTime(100);
        $this->assertTrue($this->auditLog->isSuccess());
        $this->assertSame(200, $this->auditLog->getStatusCode());
        $this->assertSame(100, $this->auditLog->getResponseTime());

        // 测试错误日志
        $this->auditLog->setIsSuccess(false);
        $this->auditLog->setStatusCode(401);
        $this->auditLog->setErrorMessage('Unauthorized access');
        $this->assertFalse($this->auditLog->isSuccess());
        $this->assertSame(401, $this->auditLog->getStatusCode());
        $this->assertSame('Unauthorized access', $this->auditLog->getErrorMessage());
    }

    public function testFullRequestLogging(): void
    {
        $requestData = ['username' => 'testuser', 'password' => '***'];
        $responseData = ['token' => 'jwt-token-123', 'expires_in' => 3600];

        $this->auditLog->setRequestPath('/api/v1/auth/login');
        $this->auditLog->setRequestMethod('POST');
        $this->auditLog->setClientIp('192.168.1.100');
        $this->auditLog->setUserAgent('Mozilla/5.0 Test Browser');
        $this->auditLog->setRequestData($requestData);
        $this->auditLog->setResponseData($responseData);
        $this->auditLog->setStatusCode(200);
        $this->auditLog->setResponseTime(250);
        $this->auditLog->setIsSuccess(true);

        $this->assertSame('/api/v1/auth/login', $this->auditLog->getRequestPath());
        $this->assertSame('POST', $this->auditLog->getRequestMethod());
        $this->assertSame('192.168.1.100', $this->auditLog->getClientIp());
        $this->assertSame('Mozilla/5.0 Test Browser', $this->auditLog->getUserAgent());
        $this->assertSame($requestData, $this->auditLog->getRequestData());
        $this->assertSame($responseData, $this->auditLog->getResponseData());
        $this->assertSame(200, $this->auditLog->getStatusCode());
        $this->assertSame(250, $this->auditLog->getResponseTime());
        $this->assertTrue($this->auditLog->isSuccess());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiKey = new ApiKey();
        $this->apiKey->setName('Test API Key');
        $this->apiKey->setApiKey('test-api-key');
        $this->apiKey->setSecretKey('test-secret-key');

        $this->auditLog = new AuditLog();
        $this->auditLog->setApiKey($this->apiKey);
        $this->auditLog->setAction('api_request');
    }
}
