<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Request;

use HttpClientBundle\Tests\Request\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\VolcanoArkApiBundle\Request\GetUsageRequest;

/**
 * @internal
 */
#[CoversClass(GetUsageRequest::class)]
class GetUsageRequestTest extends RequestTestCase
{
    public function testConstructorWithRequiredParameters(): void
    {
        $request = new GetUsageRequest(1640995200, 1640998800, 3600);

        $this->assertEquals('GetUsage', $request->getRequestPath());
        $this->assertEquals('POST', $request->getRequestMethod());
    }

    public function testConstructorWithAllParameters(): void
    {
        $request = new GetUsageRequest(
            startTime: 1640995200,
            endTime: 1640998800,
            interval: 3600,
            batchJobId: 'job-123',
            scenes: ['chat', 'completion'],
            projectName: 'test-project',
            endpointIds: ['endpoint-1', 'endpoint-2']
        );

        $options = $request->getRequestOptions();

        $this->assertNotNull($options);
        $this->assertEquals(1640995200, $options['StartTime']);
        $this->assertEquals(1640998800, $options['EndTime']);
        $this->assertEquals(3600, $options['Interval']);
        $this->assertEquals('job-123', $options['BatchJobId']);
        $this->assertEquals(['chat', 'completion'], $options['Scenes']);
        $this->assertEquals('test-project', $options['ProjectName']);
        $this->assertEquals(['endpoint-1', 'endpoint-2'], $options['EndpointIds']);
    }

    public function testGetRequestOptions(): void
    {
        $request = new GetUsageRequest(1640995200, 1640998800, 3600);
        $options = $request->getRequestOptions();

        $this->assertIsArray($options);
        $this->assertEquals(1640995200, $options['StartTime']);
        $this->assertEquals(1640998800, $options['EndTime']);
        $this->assertEquals(3600, $options['Interval']);
    }

    public function testRequestPathIsGetUsage(): void
    {
        $request = new GetUsageRequest(1640995200, 1640998800, 3600);

        $this->assertEquals('GetUsage', $request->getRequestPath());
    }

    public function testRequestMethodIsPost(): void
    {
        $request = new GetUsageRequest(1640995200, 1640998800, 3600);

        $this->assertEquals('POST', $request->getRequestMethod());
    }
}
