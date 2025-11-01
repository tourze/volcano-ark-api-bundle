<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use Tourze\VolcanoArkApiBundle\Entity\ApiKeyUsage;

/**
 * API使用统计管理控制器
 */
#[AdminCrud(routePath: '/volcano-ark-api/api-key-usage', routeName: 'volcano_ark_api_api_key_usage')]
final class ApiKeyUsageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ApiKeyUsage::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('API使用统计')
            ->setEntityLabelInPlural('API使用统计管理')
            ->setSearchFields(['apiKey.name', 'endpointId', 'batchJobId'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->setEntityPermission('ROLE_ADMIN')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setIcon('fas fa-eye')->setLabel('查看');
            })
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield AssociationField::new('apiKey', 'API密钥')
            ->setRequired(true)
            ->autocomplete()
            ->setHelp('关联的API密钥')
        ;

        yield DateTimeField::new('usageHour', '使用小时')
            ->setRequired(true)
            ->setHelp('按小时统计的使用时间')
        ;

        yield TextField::new('endpointId', '终端点ID')
            ->hideOnIndex()
            ->setHelp('API终端点标识符')
        ;

        yield TextField::new('batchJobId', '批处理任务ID')
            ->hideOnIndex()
            ->setHelp('批处理任务标识符')
        ;

        yield IntegerField::new('promptTokens', '提示词数量')
            ->setHelp('输入提示词的token数量')
        ;

        yield IntegerField::new('completionTokens', '完成词数量')
            ->setHelp('生成完成内容的token数量')
        ;

        yield IntegerField::new('totalTokens', '总token数')
            ->setHelp('总计使用的token数量')
        ;

        yield IntegerField::new('requestCount', '请求次数')
            ->setHelp('API请求的次数')
        ;

        yield NumberField::new('estimatedCost', '预估成本')
            ->setNumDecimals(4)
            ->setHelp('预估的使用成本')
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
            ->add(EntityFilter::new('apiKey', 'API密钥'))
            ->add(DateTimeFilter::new('usageHour', '使用小时'))
            ->add(NumericFilter::new('totalTokens', '总令牌数'))
        ;
    }
}
