<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\VolcanoArkApiBundle\VolcanoArkApiBundle;

/**
 * @internal
 */
#[CoversClass(VolcanoArkApiBundle::class)]
#[RunTestsInSeparateProcesses]
final class VolcanoArkApiBundleTest extends AbstractBundleTestCase
{
}
