<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOperationLog;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOperationType;

/**
 * 分账操作日志 CRUD 控制器
 *
 * 提供操作日志的只读列表和详情查看（FR-027~029）：
 * - 列表页：展示日志摘要，支持按类型/状态筛选
 * - 详情页：展示完整 JSON 负载
 * - 禁用新建、编辑、删除操作
 */
#[AdminCrud(
    routePath: '/wechat-pay-profit-share/operation-log',
    routeName: 'wechat_pay_profit_share_operation_log'
)]
#[Autoconfigure(public: true)]
final class ProfitShareOperationLogCrudController extends AbstractProfitShareCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProfitShareOperationLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInSingular('操作日志')
            ->setEntityLabelInPlural('操作日志')
            ->setPageTitle(Crud::PAGE_INDEX, '分账操作日志')
            ->setPageTitle(Crud::PAGE_DETAIL, fn (ProfitShareOperationLog $log) => sprintf('操作日志 #%s', $log->getId()))
            ->setSearchFields(['subMchId', 'errorCode', 'errorMessage'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $operationTypeChoices = [];
        foreach (ProfitShareOperationType::cases() as $case) {
            $operationTypeChoices[$case->getLabel()] = $case->value;
        }

        // 列表页字段
        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield ChoiceField::new('type', '操作类型')
            ->setChoices($operationTypeChoices)
            ->renderAsBadges([
                ProfitShareOperationType::REQUEST_ORDER->value => 'primary',
                ProfitShareOperationType::QUERY_ORDER->value => 'secondary',
                ProfitShareOperationType::UNFREEZE_ORDER->value => 'info',
                ProfitShareOperationType::REQUEST_RETURN->value => 'warning',
                ProfitShareOperationType::QUERY_RETURN->value => 'secondary',
                ProfitShareOperationType::QUERY_REMAINING_AMOUNT->value => 'secondary',
                ProfitShareOperationType::QUERY_MAX_RATIO->value => 'secondary',
                ProfitShareOperationType::ADD_RECEIVER->value => 'success',
                ProfitShareOperationType::DELETE_RECEIVER->value => 'danger',
                ProfitShareOperationType::APPLY_BILL->value => 'info',
                ProfitShareOperationType::DOWNLOAD_BILL->value => 'info',
                ProfitShareOperationType::NOTIFICATION->value => 'light',
            ])
            ->hideOnForm()
        ;

        yield AssociationField::new('merchant', '商户')
            ->hideOnForm()
            ->hideOnIndex()
        ;

        yield TextField::new('subMchId', '特约商户号')
            ->hideOnForm()
        ;

        yield BooleanField::new('success', '是否成功')
            ->renderAsSwitch(false)
            ->hideOnForm()
        ;

        yield TextField::new('errorCode', '错误码')
            ->hideOnForm()
            ->hideOnIndex()
        ;

        yield TextField::new('errorMessage', '错误信息')
            ->hideOnForm()
            ->hideOnIndex()
        ;

        // 详情页展示格式化的 JSON
        if (Crud::PAGE_DETAIL === $pageName) {
            yield CodeEditorField::new('requestPayload', '请求负载')
                ->setLanguage('javascript')
                ->hideOnForm()
                ->hideOnIndex()
                ->formatValue(fn ($value) => $this->formatJson($value))
            ;

            yield CodeEditorField::new('responsePayload', '响应负载')
                ->setLanguage('javascript')
                ->hideOnForm()
                ->hideOnIndex()
                ->formatValue(fn ($value) => $this->formatJson($value))
            ;
        }

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $operationTypeChoices = [];
        foreach (ProfitShareOperationType::cases() as $case) {
            $operationTypeChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(ChoiceFilter::new('type', '操作类型')->setChoices($operationTypeChoices))
            ->add(BooleanFilter::new('success', '是否成功'))
            ->add(TextFilter::new('subMchId', '特约商户号'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }
}
