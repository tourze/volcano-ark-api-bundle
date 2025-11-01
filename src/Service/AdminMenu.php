<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Service;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\VolcanoArkApiBundle\Controller\Admin\ApiKeyCrudController;
use Tourze\VolcanoArkApiBundle\Controller\Admin\ApiKeyUsageCrudController;
use Tourze\VolcanoArkApiBundle\Controller\Admin\AuditLogCrudController;

/**
 * Volcano Ark API 菜单提供者
 */
#[Autoconfigure(public: true)]
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        $volcanoMenu = $item->addChild('Volcano Ark API', [
            'uri' => '#',
            'attributes' => [
                'icon' => 'fas fa-volcano',
            ],
        ]);

        $volcanoMenu->addChild('API密钥管理')
            ->setUri($this->linkGenerator->getCurdListPage(ApiKeyCrudController::class))
            ->setAttribute('icon', 'fas fa-key')
        ;

        $volcanoMenu->addChild('使用统计')
            ->setUri($this->linkGenerator->getCurdListPage(ApiKeyUsageCrudController::class))
            ->setAttribute('icon', 'fas fa-chart-bar')
        ;

        $volcanoMenu->addChild('审计日志')
            ->setUri($this->linkGenerator->getCurdListPage(AuditLogCrudController::class))
            ->setAttribute('icon', 'fas fa-file-alt')
        ;
    }
}
