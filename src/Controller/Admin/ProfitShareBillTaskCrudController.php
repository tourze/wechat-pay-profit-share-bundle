<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareBillTask;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareBillStatus;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareBillRequest;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareBillService;

/**
 * 分账账单任务 CRUD 控制器
 *
 * 提供账单任务的列表查看和详情查看（FR-025~026）：
 * - 列表页：展示账单任务摘要，支持按状态/日期筛选
 * - 详情页：展示完整任务信息
 * - 支持新建（申请账单），禁用编辑、删除
 */
#[AdminCrud(
    routePath: '/wechat-pay-profit-share/bill-task',
    routeName: 'wechat_pay_profit_share_bill_task'
)]
#[Autoconfigure(public: true)]
final class ProfitShareBillTaskCrudController extends AbstractProfitShareCrudController
{
    public function __construct(
        private readonly ProfitShareBillService $billService,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ProfitShareBillTask::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInSingular('账单任务')
            ->setEntityLabelInPlural('账单任务')
            ->setPageTitle(Crud::PAGE_INDEX, '分账账单任务')
            ->setPageTitle(Crud::PAGE_DETAIL, fn (ProfitShareBillTask $task) => sprintf('账单任务 #%s', $task->getId()))
            ->setPageTitle(Crud::PAGE_NEW, '申请分账账单')
            ->setSearchFields(['subMchId'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $statusChoices = [];
        foreach (ProfitShareBillStatus::cases() as $case) {
            $statusChoices[$case->getLabel()] = $case->value;
        }

        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield AssociationField::new('merchant', '商户')
            ->setRequired(true)
            ->hideOnIndex()
        ;

        yield TextField::new('subMchId', '特约商户号')
            ->setRequired(true)
        ;

        yield DateField::new('billDate', '账单日期')
            ->setRequired(true)
        ;

        yield ChoiceField::new('status', '状态')
            ->setChoices($statusChoices)
            ->renderAsBadges([
                ProfitShareBillStatus::PENDING->value => 'secondary',
                ProfitShareBillStatus::READY->value => 'info',
                ProfitShareBillStatus::DOWNLOADED->value => 'success',
                ProfitShareBillStatus::FAILED->value => 'danger',
                ProfitShareBillStatus::EXPIRED->value => 'warning',
            ])
            ->hideOnForm()
        ;

        yield UrlField::new('downloadUrl', '下载地址')
            ->hideOnForm()
            ->hideOnIndex()
        ;

        yield DateTimeField::new('downloadedAt', '下载时间')
            ->hideOnForm()
            ->hideOnIndex()
        ;

        yield TextField::new('localPath', '本地路径')
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
        $statusChoices = [];
        foreach (ProfitShareBillStatus::cases() as $case) {
            $statusChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(ChoiceFilter::new('status', '状态')->setChoices($statusChoices))
            ->add(TextFilter::new('subMchId', '特约商户号'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }

    /**
     * 覆盖持久化方法，调用微信支付申请账单接口
     *
     * @param ProfitShareBillTask $entityInstance
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $merchant = $entityInstance->getMerchant();
        if (null === $merchant) {
            $this->addFlash('danger', '请选择商户');

            return;
        }

        $billDate = $entityInstance->getBillDate();
        if (null === $billDate) {
            $this->addFlash('danger', '请选择账单日期');

            return;
        }

        $request = new ProfitShareBillRequest(
            billDate: $billDate,
            subMchId: $entityInstance->getSubMchId(),
            tarType: $entityInstance->getTarType(),
        );

        try {
            $this->billService->applyBill($merchant, $request);
            $this->addFlash('success', '申请分账账单成功');
        } catch (\Throwable $e) {
            $this->addFlash('danger', sprintf('申请分账账单失败：%s', $e->getMessage()));
        }
    }
}
