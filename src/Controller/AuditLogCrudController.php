<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\VolcanoArkApiBundle\Entity\AuditLog;

/**
 * 审计日志管理控制器
 */
#[AdminCrud(routePath: '/volcano-ark-api/audit-log', routeName: 'volcano_ark_api_audit_log')]
final class AuditLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AuditLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('审计日志')
            ->setEntityLabelInPlural('审计日志管理')
            ->setSearchFields(['action', 'description', 'requestPath', 'clientIp'])
            ->setDefaultSort(['createTime' => 'DESC'])
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

        yield TextField::new('action', '操作类型')
            ->setRequired(true)
            ->setHelp('执行的操作类型')
        ;

        yield TextareaField::new('description', '操作描述')
            ->hideOnIndex()
            ->setHelp('操作的详细描述')
        ;

        yield TextField::new('requestPath', '请求路径')
            ->hideOnIndex()
            ->setHelp('API请求的路径')
        ;

        yield TextField::new('requestMethod', '请求方法')
            ->hideOnIndex()
            ->setHelp('HTTP请求方法')
        ;

        yield TextField::new('clientIp', '客户端IP')
            ->setHelp('发起请求的客户端IP地址')
        ;

        yield TextField::new('userAgent', '用户代理')
            ->hideOnIndex()
            ->setHelp('客户端的用户代理信息')
        ;

        yield IntegerField::new('statusCode', '状态码')
            ->setHelp('HTTP响应状态码')
        ;

        yield IntegerField::new('responseTime', '响应时间(毫秒)')
            ->setHelp('API响应时间，单位为毫秒')
        ;

        yield BooleanField::new('isSuccess', '是否成功')
            ->setHelp('操作是否成功执行')
        ;

        yield TextareaField::new('errorMessage', '错误信息')
            ->hideOnIndex()
            ->setHelp('如果操作失败，记录的错误信息')
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setHelp('审计日志的创建时间')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('apiKey', 'API密钥'))
            ->add(TextFilter::new('action', '操作类型'))
            ->add(BooleanFilter::new('isSuccess', '是否成功'))
            ->add(NumericFilter::new('statusCode', '状态码'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }
}
