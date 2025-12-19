<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\VolcanoArkApiBundle\Service\AdminMenu;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
#[Group('volcano-ark-api-bundle')]
class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private AdminMenu $adminMenu;
    private LinkGeneratorInterface $linkGenerator;

    protected function onSetUp(): void
    {
        $this->adminMenu = self::getService(AdminMenu::class);
        $this->linkGenerator = self::getService(LinkGeneratorInterface::class);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(AdminMenu::class, $this->adminMenu);
    }

    public function testMenuProviderInterface(): void
    {
        $this->assertInstanceOf(MenuProviderInterface::class, $this->adminMenu);
    }

    public function testInvokeCreatesVolcanoArkMenu(): void
    {
        $rootItem = $this->createMock(ItemInterface::class);
        $volcanoMenu = $this->createMock(ItemInterface::class);
        $apiKeyMenu = $this->createMock(ItemInterface::class);
        $usageMenu = $this->createMock(ItemInterface::class);
        $auditMenu = $this->createMock(ItemInterface::class);

        $rootItem->expects($this->once())
            ->method('addChild')
            ->with(
                'Volcano Ark API',
                [
                    'uri' => '#',
                    'attributes' => [
                        'icon' => 'fas fa-volcano',
                    ],
                ]
            )
            ->willReturn($volcanoMenu)
        ;

        $volcanoMenu->expects($this->exactly(3))
            ->method('addChild')
            ->willReturnOnConsecutiveCalls($apiKeyMenu, $usageMenu, $auditMenu)
        ;

        // 配置API密钥菜单项的链式调用
        $apiKeyMenu->expects($this->once())
            ->method('setUri')
            ->with('/admin/volcano-ark-api/api-key')
            ->willReturn($apiKeyMenu)
        ;

        $apiKeyMenu->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-key')
        ;

        // 配置使用统计菜单项的链式调用
        $usageMenu->expects($this->once())
            ->method('setUri')
            ->with('/admin/volcano-ark-api/api-key-usage')
            ->willReturn($usageMenu)
        ;

        $usageMenu->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-chart-bar')
        ;

        // 配置审计日志菜单项的链式调用
        $auditMenu->expects($this->once())
            ->method('setUri')
            ->with('/admin/volcano-ark-api/audit-log')
            ->willReturn($auditMenu)
        ;

        $auditMenu->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-file-alt')
        ;

        ($this->adminMenu)($rootItem);
    }

    public function testLinkGeneratorIntegration(): void
    {
        // 测试 LinkGenerator 是否正确集成
        $this->assertInstanceOf(LinkGeneratorInterface::class, $this->linkGenerator);

        // 验证 LinkGenerator 实例可用
        $this->assertNotNull($this->linkGenerator);
    }
}