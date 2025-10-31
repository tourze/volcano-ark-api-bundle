<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\VolcanoArkApiBundle\Service\VolcanoArkOpenAiClientProvider;

/**
 * @internal
 */
#[CoversClass(VolcanoArkOpenAiClientProvider::class)]
#[RunTestsInSeparateProcesses]
class VolcanoArkOpenAiClientProviderTest extends AbstractIntegrationTestCase
{
    private VolcanoArkOpenAiClientProvider $provider;

    protected function onSetUp(): void
    {
        // 确保服务可用，测试不应跳过
        $this->provider = self::getService(VolcanoArkOpenAiClientProvider::class);
        $this->assertInstanceOf(VolcanoArkOpenAiClientProvider::class, $this->provider, 'VolcanoArkOpenAiClientProvider service must be available');
    }

    public function testServiceExists(): void
    {
        $this->assertInstanceOf(VolcanoArkOpenAiClientProvider::class, $this->provider);
    }

    public function testFetchOpenAiClient(): void
    {
        // 测试获取客户端生成器
        $generator = $this->provider->fetchOpenAiClient();
        $this->assertInstanceOf(\Generator::class, $generator);
    }

    public function testFetchOpenAiClientWithConfig(): void
    {
        // 测试带配置获取客户端生成器
        $generator = $this->provider->fetchOpenAiClientWithConfig([]);
        $this->assertInstanceOf(\Generator::class, $generator);
    }
}
