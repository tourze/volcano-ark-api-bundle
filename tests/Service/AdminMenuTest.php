<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
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

    private LinkGeneratorInterface&MockObject $linkGenerator;

    protected function onSetUp(): void
    {
        $this->linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        static::getContainer()->set(LinkGeneratorInterface::class, $this->linkGenerator);
        $this->adminMenu = self::getService(AdminMenu::class);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(AdminMenu::class, $this->adminMenu);
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

        $this->linkGenerator->expects($this->exactly(3))
            ->method('getCurdListPage')
            ->willReturnOnConsecutiveCalls(
                '/admin/api-key',
                '/admin/api-key-usage',
                '/admin/audit-log'
            )
        ;

        $volcanoMenu->expects($this->exactly(3))
            ->method('addChild')
            ->willReturnOnConsecutiveCalls($apiKeyMenu, $usageMenu, $auditMenu)
        ;

        // 配置API密钥菜单项的链式调用
        $apiKeyMenu->expects($this->once())
            ->method('setUri')
            ->with('/admin/api-key')
            ->willReturn($apiKeyMenu)
        ;

        $apiKeyMenu->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-key')
        ;

        // 配置使用统计菜单项的链式调用
        $usageMenu->expects($this->once())
            ->method('setUri')
            ->with('/admin/api-key-usage')
            ->willReturn($usageMenu)
        ;

        $usageMenu->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-chart-bar')
        ;

        // 配置审计日志菜单项的链式调用
        $auditMenu->expects($this->once())
            ->method('setUri')
            ->with('/admin/audit-log')
            ->willReturn($auditMenu)
        ;

        $auditMenu->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-file-alt')
        ;

        ($this->adminMenu)($rootItem);
    }

    public function testMenuProviderInterface(): void
    {
        $this->assertInstanceOf(MenuProviderInterface::class, $this->adminMenu);
    }
}
