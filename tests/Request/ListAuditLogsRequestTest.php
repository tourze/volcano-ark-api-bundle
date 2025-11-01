<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\VolcanoArkApiBundle\DTO\AuditLogFilter;
use Tourze\VolcanoArkApiBundle\Request\ListAuditLogsRequest;

/**
 * @internal
 */
#[CoversClass(ListAuditLogsRequest::class)]
class ListAuditLogsRequestTest extends RequestTestCase
{
    public function testConstructorWithDefaults(): void
    {
        $filter = new AuditLogFilter();
        $request = new ListAuditLogsRequest('test-resource-id', 'test-resource-type', $filter);

        $this->assertEquals('ListAuditLogs', $request->getRequestPath());
        $this->assertEquals('POST', $request->getRequestMethod());
    }

    public function testConstructorWithParameters(): void
    {
        $filter = new AuditLogFilter('audit', 'high', '2024-01-01T00:00:00Z', '2024-01-01T23:59:59Z');
        $request = new ListAuditLogsRequest('test-resource-id', 'test-resource-type', $filter, 'test-project', 2, 50);

        $options = $request->getRequestOptions();

        $this->assertNotNull($options);
        $this->assertIsArray($options);
        $this->assertEquals(2, $options['PageNumber']);
        $this->assertEquals(50, $options['PageSize']);
        $this->assertArrayHasKey('Filter', $options);
        $filterData = $options['Filter'];
        $this->assertIsArray($filterData);
        $this->assertEquals('audit', $filterData['LogType']);
    }

    public function testGetRequestOptions(): void
    {
        $filter = new AuditLogFilter('security', 'medium');
        $request = new ListAuditLogsRequest('test-resource-id', 'test-resource-type', $filter);
        $options = $request->getRequestOptions();

        $this->assertIsArray($options);
        $this->assertEquals(1, $options['PageNumber']);
        $this->assertEquals(10, $options['PageSize']);
        $filterData = $options['Filter'];
        $this->assertIsArray($filterData);
        $this->assertEquals('security', $filterData['LogType']);
        $this->assertEquals('medium', $filterData['RiskLevel']);
    }

    public function testRequestPathIsListAuditLogs(): void
    {
        $filter = new AuditLogFilter();
        $request = new ListAuditLogsRequest('test-resource-id', 'test-resource-type', $filter);

        $this->assertEquals('ListAuditLogs', $request->getRequestPath());
    }

    public function testRequestMethodIsPost(): void
    {
        $filter = new AuditLogFilter();
        $request = new ListAuditLogsRequest('test-resource-id', 'test-resource-type', $filter);

        $this->assertEquals('POST', $request->getRequestMethod());
    }

    public function testWithNullFilter(): void
    {
        $filter = new AuditLogFilter();
        $request = new ListAuditLogsRequest('test-resource-id', 'test-resource-type', $filter);
        $options = $request->getRequestOptions();

        $this->assertNotNull($options);
        $this->assertEquals(1, $options['PageNumber']);
        $this->assertEquals(10, $options['PageSize']);
        $this->assertArrayHasKey('Filter', $options);
    }
}
