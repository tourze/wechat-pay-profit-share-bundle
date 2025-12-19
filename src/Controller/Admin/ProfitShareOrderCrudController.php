<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Response;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReceiver;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOrderState;
use Tourze\WechatPayProfitShareBundle\Form\ProfitShareReceiverType;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareOrderRequest;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReceiverRequest;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReturnRequest;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareUnfreezeRequest;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareReturnService;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareService;

/**
 * 分账订单 CRUD 控制器
 *
 * 提供分账订单的完整管理功能（FR-001~019）：
 * - 列表页：展示订单摘要，支持按状态/商户筛选（FR-006~009）
 * - 详情页：展示完整订单信息及接收方列表（FR-013~014）
 * - 新建：创建分账订单（FR-001~005）
 * - 操作：解冻剩余资金（FR-010~012）、发起回退（FR-015~019）
 */
#[AdminCrud(
    routePath: '/wechat-pay-profit-share/order',
    routeName: 'wechat_pay_profit_share_order'
)]
#[Autoconfigure(public: true)]
final class ProfitShareOrderCrudController extends AbstractProfitShareCrudController
{
    public function __construct(
        private readonly ProfitShareService $profitShareService,
        private readonly ProfitShareReturnService $returnService,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ProfitShareOrder::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInSingular('分账订单')
            ->setEntityLabelInPlural('分账订单')
            ->setPageTitle(Crud::PAGE_INDEX, '分账订单管理')
            ->setPageTitle(Crud::PAGE_DETAIL, fn (ProfitShareOrder $order) => sprintf('分账订单 %s', $order->getOutOrderNo()))
            ->setPageTitle(Crud::PAGE_NEW, '创建分账订单')
            ->setSearchFields(['outOrderNo', 'orderId', 'transactionId', 'subMchId'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $unfreezeAction = Action::new('unfreeze', '解冻剩余资金')
            ->linkToCrudAction('unfreezeAction')
            ->displayIf(fn (ProfitShareOrder $order) => ProfitShareOrderState::PROCESSING === $order->getState())
            ->setCssClass('btn btn-warning')
        ;

        $returnAction = Action::new('return', '发起回退')
            ->linkToCrudAction('returnAction')
            ->displayIf(fn (ProfitShareOrder $order) => ProfitShareOrderState::FINISHED === $order->getState())
            ->setCssClass('btn btn-danger')
        ;

        return $actions
            ->disable(Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $unfreezeAction)
            ->add(Crud::PAGE_DETAIL, $returnAction)
        ;
    }

    /**
     * 解冻剩余资金操作 (FR-010~012)
     */
    #[AdminAction(routeName: 'wechat_pay_profit_share_order_unfreeze', routePath: '/unfreeze/{entityId}')]
    public function unfreezeAction(AdminContext $context): Response
    {
        /** @var ProfitShareOrder $order */
        $order = $context->getEntity()->getInstance();

        $merchant = $order->getMerchant();
        if (null === $merchant) {
            $this->addFlash('danger', '订单未关联商户');

            return $this->redirectToDetailPage($order);
        }

        $request = new ProfitShareUnfreezeRequest(
            subMchId: $order->getSubMchId(),
            transactionId: $order->getTransactionId(),
            outOrderNo: $order->getOutOrderNo(),
            description: '解冻剩余未分账资金',
            unfreezeUnsplit: true,
        );

        try {
            $this->profitShareService->unfreezeRemainingAmount($merchant, $request);
            $this->addFlash('success', '解冻剩余资金成功');
        } catch (\Throwable $e) {
            $this->addFlash('danger', sprintf('解冻剩余资金失败：%s', $e->getMessage()));
        }

        return $this->redirectToDetailPage($order);
    }

    /**
     * 发起回退操作 (FR-015~019)
     */
    #[AdminAction(routeName: 'wechat_pay_profit_share_order_return', routePath: '/return/{entityId}')]
    public function returnAction(AdminContext $context): Response
    {
        /** @var ProfitShareOrder $order */
        $order = $context->getEntity()->getInstance();

        $merchant = $order->getMerchant();
        if (null === $merchant) {
            $this->addFlash('danger', '订单未关联商户');

            return $this->redirectToDetailPage($order);
        }

        $receivers = $order->getReceivers();
        if ($receivers->isEmpty()) {
            $this->addFlash('danger', '该订单没有接收方可回退');

            return $this->redirectToDetailPage($order);
        }

        $firstReceiver = $receivers->first();
        if (false === $firstReceiver) {
            $this->addFlash('danger', '无法获取接收方信息');

            return $this->redirectToDetailPage($order);
        }

        $outReturnNo = sprintf('R%s%s', date('YmdHis'), substr(uniqid(), -6));

        $request = new ProfitShareReturnRequest(
            subMchId: $order->getSubMchId() ?? '',
            outReturnNo: $outReturnNo,
            amount: $firstReceiver->getAmount() ?? 0,
            description: '分账回退',
            orderId: $order->getOrderId(),
            outOrderNo: $order->getOutOrderNo(),
        );

        try {
            $this->returnService->requestReturn($merchant, $request);
            $this->addFlash('success', sprintf('发起回退成功，回退单号：%s', $outReturnNo));
        } catch (\Throwable $e) {
            $this->addFlash('danger', sprintf('发起回退失败：%s', $e->getMessage()));
        }

        return $this->redirectToDetailPage($order);
    }

    private function redirectToDetailPage(ProfitShareOrder $order): Response
    {
        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($order->getId())
            ->generateUrl()
        ;

        return $this->redirect($url);
    }

    public function configureFields(string $pageName): iterable
    {
        $stateChoices = [];
        foreach (ProfitShareOrderState::cases() as $case) {
            $stateChoices[$case->getLabel()] = $case->value;
        }

        // ID
        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        // 商户关联
        yield AssociationField::new('merchant', '商户')
            ->setRequired(true)
            ->hideOnIndex()
        ;

        // 特约商户号
        yield TextField::new('subMchId', '特约商户号')
            ->setRequired(true)
        ;

        // 微信支付订单号
        yield TextField::new('transactionId', '微信支付订单号')
            ->setRequired(true)
        ;

        // 商户分账单号
        yield TextField::new('outOrderNo', '商户分账单号')
            ->setRequired(true)
        ;

        // 微信分账单号
        yield TextField::new('orderId', '微信分账单号')
            ->hideOnForm()
        ;

        // 分账状态
        yield ChoiceField::new('state', '分账状态')
            ->setChoices($stateChoices)
            ->renderAsBadges([
                ProfitShareOrderState::PROCESSING->value => 'secondary',
                ProfitShareOrderState::FINISHED->value => 'success',
                ProfitShareOrderState::CLOSED->value => 'danger',
            ])
            ->hideOnForm()
        ;

        // 是否解冻
        yield BooleanField::new('unfreezeUnsplit', '解冻剩余资金')
            ->renderAsSwitch(false)
            ->setHelp('是否在分账完成后自动解冻剩余未分配资金')
        ;

        // 详情页展示接收方列表
        if (Crud::PAGE_DETAIL === $pageName) {
            yield AssociationField::new('receivers', '分账接收方')
                ->hideOnForm()
                ->hideOnIndex()
            ;
        }

        // 新建表单中添加接收方
        if (Crud::PAGE_NEW === $pageName) {
            yield CollectionField::new('receivers', '分账接收方')
                ->setEntryType(ProfitShareReceiverType::class)
                ->allowAdd()
                ->allowDelete()
                ->setHelp('添加分账接收方，每个接收方需指定类型、账号、金额和描述')
            ;
        }

        // 详情页展示更多信息
        if (Crud::PAGE_DETAIL === $pageName) {
            yield TextField::new('appId', '公众账号ID')
                ->hideOnForm()
                ->hideOnIndex()
            ;

            yield TextField::new('subAppId', '特约商户公众账号ID')
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

        // 时间戳
        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->hideOnIndex()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $stateChoices = [];
        foreach (ProfitShareOrderState::cases() as $case) {
            $stateChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(ChoiceFilter::new('state', '分账状态')->setChoices($stateChoices))
            ->add(TextFilter::new('subMchId', '特约商户号'))
            ->add(TextFilter::new('outOrderNo', '商户分账单号'))
            ->add(TextFilter::new('transactionId', '微信支付订单号'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }

    /**
     * 覆盖持久化方法，调用微信支付分账接口 (FR-001~005, T024~T026)
     *
     * @param ProfitShareOrder $entityInstance
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $merchant = $entityInstance->getMerchant();
        if (null === $merchant) {
            $this->addFlash('danger', '请选择商户');

            return;
        }

        $subMchId = $entityInstance->getSubMchId();
        if (null === $subMchId || '' === $subMchId) {
            $this->addFlash('danger', '请填写特约商户号');

            return;
        }

        $transactionId = $entityInstance->getTransactionId();
        if (null === $transactionId || '' === $transactionId) {
            $this->addFlash('danger', '请填写微信支付订单号');

            return;
        }

        $outOrderNo = $entityInstance->getOutOrderNo();
        if (null === $outOrderNo || '' === $outOrderNo) {
            $this->addFlash('danger', '请填写商户分账单号');

            return;
        }

        $receivers = $entityInstance->getReceivers();
        if ($receivers->isEmpty()) {
            $this->addFlash('danger', '请添加至少一个分账接收方');

            return;
        }

        $request = new ProfitShareOrderRequest(
            subMchId: $subMchId,
            transactionId: $transactionId,
            outOrderNo: $outOrderNo,
        );
        $request->setUnfreezeUnsplit($entityInstance->isUnfreezeUnsplit());

        foreach ($receivers as $receiver) {
            /** @var ProfitShareReceiver $receiver */
            $receiverType = $receiver->getType();
            $receiverAccount = $receiver->getAccount();
            $receiverAmount = $receiver->getAmount();
            $receiverDescription = $receiver->getDescription();

            if (null === $receiverType || null === $receiverAccount || null === $receiverAmount || null === $receiverDescription) {
                $this->addFlash('danger', '接收方信息不完整');

                return;
            }

            $receiverRequest = new ProfitShareReceiverRequest(
                type: $receiverType,
                account: $receiverAccount,
                amount: $receiverAmount,
                description: $receiverDescription,
                name: $receiver->getName(),
            );
            $request->addReceiver($receiverRequest);
        }

        try {
            $this->profitShareService->requestProfitShare($merchant, $request);
            $this->addFlash('success', '创建分账订单成功');
        } catch (\Throwable $e) {
            $this->addFlash('danger', sprintf('创建分账订单失败：%s', $e->getMessage()));
        }
    }
}
