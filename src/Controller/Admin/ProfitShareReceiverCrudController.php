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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReceiver;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareReceiverResult;

/**
 * 分账接收方 CRUD 控制器
 *
 * 提供分账接收方的只读列表和详情查看（FR-013~014）：
 * - 列表页：展示接收方摘要，支持按结果筛选
 * - 详情页：展示完整接收方信息（姓名脱敏）
 * - 禁用新建、编辑、删除操作（接收方通过订单关联创建）
 */
#[AdminCrud(
    routePath: '/wechat-pay-profit-share/receiver',
    routeName: 'wechat_pay_profit_share_receiver'
)]
#[Autoconfigure(public: true)]
final class ProfitShareReceiverCrudController extends AbstractProfitShareCrudController
{
    /**
     * 接收方类型选项
     */
    private const TYPE_CHOICES = [
        '商户号' => 'MERCHANT_ID',
        '个人openid' => 'PERSONAL_OPENID',
        '个人sub_openid' => 'PERSONAL_SUB_OPENID',
    ];

    public static function getEntityFqcn(): string
    {
        return ProfitShareReceiver::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInSingular('分账接收方')
            ->setEntityLabelInPlural('分账接收方')
            ->setPageTitle(Crud::PAGE_INDEX, '分账接收方')
            ->setPageTitle(Crud::PAGE_DETAIL, fn (ProfitShareReceiver $receiver) => sprintf('接收方 %s', $receiver->getAccount()))
            ->setSearchFields(['account', 'description'])
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
        $resultChoices = [];
        foreach (ProfitShareReceiverResult::cases() as $case) {
            $resultChoices[$case->getLabel()] = $case->value;
        }

        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield AssociationField::new('order', '分账订单')
            ->hideOnForm()
        ;

        yield ChoiceField::new('type', '接收方类型')
            ->setChoices(self::TYPE_CHOICES)
            ->hideOnForm()
        ;

        yield TextField::new('account', '接收方账号')
            ->hideOnForm()
        ;

        // 姓名脱敏展示
        yield TextField::new('name', '接收方姓名')
            ->hideOnForm()
            ->formatValue(fn ($value) => $this->maskName($value))
        ;

        yield IntegerField::new('amount', '分账金额')
            ->hideOnForm()
            ->formatValue(fn ($value) => is_numeric($value) ? $this->formatAmount((int) $value) : $this->formatAmount(null))
        ;

        yield TextField::new('description', '分账描述')
            ->hideOnForm()
        ;

        yield ChoiceField::new('result', '分账结果')
            ->setChoices($resultChoices)
            ->renderAsBadges([
                ProfitShareReceiverResult::PENDING->value => 'secondary',
                ProfitShareReceiverResult::SUCCESS->value => 'success',
                ProfitShareReceiverResult::CLOSED->value => 'warning',
                ProfitShareReceiverResult::FAILED->value => 'danger',
            ])
            ->hideOnForm()
        ;

        yield TextField::new('failReason', '失败原因')
            ->hideOnForm()
            ->hideOnIndex()
        ;

        yield TextField::new('detailId', '分账明细单号')
            ->hideOnForm()
            ->hideOnIndex()
        ;

        // 详情页展示更多信息
        if (Crud::PAGE_DETAIL === $pageName) {
            yield IntegerField::new('sequence', '接收方顺序')
                ->hideOnForm()
                ->hideOnIndex()
            ;

            yield IntegerField::new('retryCount', '重试次数')
                ->hideOnForm()
                ->hideOnIndex()
            ;

            yield DateTimeField::new('nextRetryAt', '下次重试时间')
                ->hideOnForm()
                ->hideOnIndex()
            ;

            yield BooleanField::new('finallyFailed', '是否最终失败')
                ->renderAsSwitch(false)
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
        }

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $resultChoices = [];
        foreach (ProfitShareReceiverResult::cases() as $case) {
            $resultChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(ChoiceFilter::new('result', '分账结果')->setChoices($resultChoices))
            ->add(ChoiceFilter::new('type', '接收方类型')->setChoices(self::TYPE_CHOICES))
            ->add(TextFilter::new('account', '接收方账号'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }
}
