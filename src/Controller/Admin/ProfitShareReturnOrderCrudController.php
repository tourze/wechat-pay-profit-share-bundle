<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReturnOrder;

/**
 * 分账回退单 CRUD 控制器
 *
 * 提供回退单的只读列表和详情查看（FR-020~021）：
 * - 列表页：展示回退单摘要，支持按结果/商户筛选
 * - 详情页：展示完整回退信息及失败原因
 * - 禁用新建、编辑、删除操作（回退通过订单操作发起）
 */
#[AdminCrud(
    routePath: '/wechat-pay-profit-share/return-order',
    routeName: 'wechat_pay_profit_share_return_order'
)]
#[Autoconfigure(public: true)]
final class ProfitShareReturnOrderCrudController extends AbstractProfitShareCrudController
{
    /**
     * 回退结果选项
     */
    private const RESULT_CHOICES = [
        '处理中' => 'PROCESSING',
        '成功' => 'SUCCESS',
        '失败' => 'FAILED',
    ];

    public static function getEntityFqcn(): string
    {
        return ProfitShareReturnOrder::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInSingular('回退单')
            ->setEntityLabelInPlural('回退单')
            ->setPageTitle(Crud::PAGE_INDEX, '分账回退单')
            ->setPageTitle(Crud::PAGE_DETAIL, fn (ProfitShareReturnOrder $order) => sprintf('回退单 %s', $order->getOutReturnNo()))
            ->setSearchFields(['outReturnNo', 'returnNo', 'outOrderNo', 'subMchId'])
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
        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield TextField::new('outReturnNo', '商户回退单号')
            ->hideOnForm()
        ;

        yield TextField::new('returnNo', '微信回退单号')
            ->hideOnForm()
            ->hideOnIndex()
        ;

        yield TextField::new('outOrderNo', '商户分账单号')
            ->hideOnForm()
        ;

        yield TextField::new('orderId', '微信分账单号')
            ->hideOnForm()
            ->hideOnIndex()
        ;

        yield AssociationField::new('merchant', '商户')
            ->hideOnForm()
            ->hideOnIndex()
        ;

        yield TextField::new('subMchId', '特约商户号')
            ->hideOnForm()
        ;

        yield IntegerField::new('amount', '回退金额')
            ->hideOnForm()
            ->formatValue(fn ($value) => is_numeric($value) ? $this->formatAmount((int) $value) : $this->formatAmount(null))
        ;

        yield TextField::new('description', '回退描述')
            ->hideOnForm()
            ->hideOnIndex()
        ;

        yield TextField::new('result', '回退结果')
            ->hideOnForm()
            ->formatValue(function (?string $value): string {
                if (null === $value) {
                    return '未知';
                }

                return match ($value) {
                    'PROCESSING' => '<span class="badge bg-secondary">处理中</span>',
                    'SUCCESS' => '<span class="badge bg-success">成功</span>',
                    'FAILED' => '<span class="badge bg-danger">失败</span>',
                    default => $value,
                };
            })
        ;

        yield TextField::new('failReason', '失败原因')
            ->hideOnForm()
            ->hideOnIndex()
        ;

        yield DateTimeField::new('wechatCreatedAt', '微信创建时间')
            ->hideOnForm()
            ->hideOnIndex()
        ;

        yield DateTimeField::new('wechatFinishedAt', '微信完成时间')
            ->hideOnForm()
            ->hideOnIndex()
        ;

        // 详情页展示格式化的 JSON
        if (Crud::PAGE_DETAIL === $pageName) {
            yield CodeEditorField::new('requestPayload', '请求负载')
                ->setLanguage('javascript')
                ->hideOnForm()
                ->hideOnIndex()
                ->formatValue(fn ($value) => $this->maskSensitiveJson($value))
            ;

            yield CodeEditorField::new('responsePayload', '响应负载')
                ->setLanguage('javascript')
                ->hideOnForm()
                ->hideOnIndex()
                ->formatValue(fn ($value) => $this->maskSensitiveJson($value))
            ;
        }

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('result', '回退结果')->setChoices(self::RESULT_CHOICES))
            ->add(TextFilter::new('subMchId', '特约商户号'))
            ->add(TextFilter::new('outReturnNo', '商户回退单号'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }
}
