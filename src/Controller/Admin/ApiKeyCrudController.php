<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;

/**
 * API密钥管理控制器
 */
#[AdminCrud(routePath: '/volcano-ark-api/api-key', routeName: 'volcano_ark_api_api_key')]
final class ApiKeyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ApiKey::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('API密钥')
            ->setEntityLabelInPlural('API密钥管理')
            ->setSearchFields(['name', 'provider', 'region', 'description'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->setEntityPermission('ROLE_ADMIN')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield TextField::new('name', '密钥名称')
            ->setRequired(true)
            ->setHelp('用于标识此API密钥的名称')
        ;

        yield TextField::new('provider', '服务提供商')
            ->setRequired(true)
            ->setHelp('API服务提供商，默认为volcano_ark')
        ;

        yield TextField::new('apiKey', 'API密钥')
            ->setRequired(true)
            ->hideOnIndex()
            ->setHelp('从服务提供商获取的API密钥')
        ;

        yield TextareaField::new('secretKey', '密钥值')
            ->setRequired(true)
            ->hideOnIndex()
            ->setHelp('从服务提供商获取的密钥值')
        ;

        yield TextField::new('region', '区域')
            ->setRequired(true)
            ->setHelp('API服务区域，如cn-beijing')
        ;

        yield BooleanField::new('isActive', '是否激活')
            ->setHelp('是否启用此API密钥')
        ;

        yield IntegerField::new('usageCount', '使用次数')
            ->hideOnForm()
            ->setHelp('API密钥的总使用次数')
        ;

        yield DateTimeField::new('lastUsedTime', '最后使用时间')
            ->hideOnForm()
            ->setHelp('API密钥最后一次使用的时间')
        ;

        yield TextareaField::new('description', '描述')
            ->hideOnIndex()
            ->setHelp('API密钥的详细描述')
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', '密钥名称'))
            ->add(TextFilter::new('provider', '服务提供商'))
            ->add(BooleanFilter::new('isActive', '是否激活'))
        ;
    }
}
