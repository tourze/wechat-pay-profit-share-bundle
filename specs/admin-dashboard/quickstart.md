# 快速入门：微信支付分账后台管理界面

**Feature**: `admin-dashboard`
**日期**: 2025-11-28

## 概述

本文档为开发者提供快速实现分账后台管理界面的指南。

---

## 目录结构

```
packages/wechat-pay-profit-share-bundle/
├── src/
│   └── Controller/
│       └── Admin/
│           ├── ProfitShareOrderCrudController.php      # 分账订单管理
│           ├── ProfitShareReceiverCrudController.php   # 接收方管理（只读）
│           ├── ProfitShareReturnOrderCrudController.php # 回退单管理
│           ├── ProfitShareBillTaskCrudController.php   # 账单任务管理
│           └── ProfitShareOperationLogCrudController.php # 操作日志
└── tests/
    └── Controller/
        └── Admin/
            ├── ProfitShareOrderCrudControllerTest.php
            ├── ProfitShareReceiverCrudControllerTest.php
            ├── ProfitShareReturnOrderCrudControllerTest.php
            ├── ProfitShareBillTaskCrudControllerTest.php
            └── ProfitShareOperationLogCrudControllerTest.php
```

---

## 实现顺序

按照依赖关系，推荐实现顺序：

1. **ProfitShareOperationLogCrudController**（只读，无依赖）
2. **ProfitShareBillTaskCrudController**（申请账单操作）
3. **ProfitShareReturnOrderCrudController**（只读）
4. **ProfitShareReceiverCrudController**（只读，关联订单）
5. **ProfitShareOrderCrudController**（核心，含解冻/回退操作）

---

## 快速开始

### 1. 创建基础 CrudController

```php
<?php

namespace WechatPayProfitShareBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Tourze\EasyAdminPlusBundle\Attribute\AdminCrud;
use WechatPayProfitShareBundle\Entity\ProfitShareOperationLog;

#[AdminCrud(routePath: '/profit-share/log', routeName: 'profit_share_log')]
class ProfitShareOperationLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProfitShareOperationLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('操作日志')
            ->setEntityLabelInPlural('操作日志')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['subMchId', 'errorCode', 'errorMessage'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();
        yield TextField::new('type', '操作类型');
        yield TextField::new('subMchId', '特约商户号');
        yield BooleanField::new('success', '是否成功');
        yield TextField::new('errorCode', '错误码')->hideOnIndex();
        yield TextField::new('errorMessage', '错误信息');
        yield DateTimeField::new('createTime', '创建时间');
    }
}
```

### 2. 添加自定义 Action（解冻资金示例）

```php
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

public function configureActions(Actions $actions): Actions
{
    $unfreezeAction = Action::new('unfreeze', '解冻剩余资金')
        ->linkToCrudAction('unfreezeAction')
        ->displayIf(fn (ProfitShareOrder $order) =>
            $order->getState() === ProfitShareOrderState::FINISHED
            && !$order->isUnfreezeUnsplit()
        )
    ;

    return $actions
        ->add(Crud::PAGE_DETAIL, $unfreezeAction)
    ;
}

public function unfreezeAction(AdminContext $context): Response
{
    $order = $context->getEntity()->getInstance();

    try {
        $this->profitShareService->unfreezeRemainingAmount(/* ... */);
        $this->addFlash('success', '资金解冻成功');
    } catch (\Exception $e) {
        $this->addFlash('danger', '解冻失败：' . $e->getMessage());
    }

    return $this->redirect(
        $this->container->get(AdminUrlGenerator::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($order->getId())
            ->generateUrl()
    );
}
```

### 3. 使用 CollectionField（接收方列表）

```php
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;

yield CollectionField::new('receivers', '接收方')
    ->setEntryType(ProfitShareReceiverType::class)
    ->allowAdd()
    ->allowDelete()
    ->setRequired(true)
    ->onlyOnForms()
;
```

对应的 FormType：

```php
<?php

namespace WechatPayProfitShareBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ProfitShareReceiverType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => '类型',
                'choices' => [
                    '商户号' => 'MERCHANT_ID',
                    '个人OpenID' => 'PERSONAL_OPENID',
                ],
            ])
            ->add('account', TextType::class, ['label' => '账号'])
            ->add('name', TextType::class, ['label' => '姓名', 'required' => false])
            ->add('amount', IntegerType::class, ['label' => '金额（分）'])
            ->add('description', TextType::class, ['label' => '描述'])
        ;
    }
}
```

### 4. 敏感数据脱敏

```php
yield TextareaField::new('requestPayload', '请求负载')
    ->formatValue(fn (?string $json) => $this->maskSensitiveData($json))
    ->hideOnIndex()
;

private function maskSensitiveData(?string $json): string
{
    if (null === $json || '' === $json) {
        return '无';
    }

    $decoded = json_decode($json, true);
    if (JSON_ERROR_NONE !== json_last_error()) {
        return $json;
    }

    // 脱敏敏感字段
    $sensitiveFields = ['name', 'idcard_number', 'phone_number'];
    array_walk_recursive($decoded, function (&$value, $key) use ($sensitiveFields) {
        if (in_array($key, $sensitiveFields, true) && is_string($value)) {
            $value = mb_substr($value, 0, 1) . '***' . mb_substr($value, -1);
        }
    });

    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
```

---

## 依赖服务

| 服务 | 说明 | 使用场景 |
|------|------|----------|
| ProfitShareService | 分账核心服务 | 创建订单、解冻资金 |
| ProfitShareReturnService | 回退服务 | 发起回退 |
| ProfitShareBillService | 账单服务 | 申请账单 |

---

## 测试策略

### 单元测试基类

```php
<?php

namespace WechatPayProfitShareBundle\Tests\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AdminWebTestCase extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // 登录 admin 用户
        // 设置测试数据
    }

    protected function assertPageLoads(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
    }
}
```

### 运行测试

```bash
# 运行所有 Admin 测试
./vendor/bin/phpunit packages/wechat-pay-profit-share-bundle/tests/Controller/Admin/

# 运行单个控制器测试
./vendor/bin/phpunit packages/wechat-pay-profit-share-bundle/tests/Controller/Admin/ProfitShareOrderCrudControllerTest.php
```

---

## 质量门检查

```bash
# PHPStan 静态分析
./vendor/bin/phpstan analyse -c phpstan.neon packages/wechat-pay-profit-share-bundle/src/Controller/Admin/

# PHP CS Fixer 代码格式
./vendor/bin/php-cs-fixer fix packages/wechat-pay-profit-share-bundle/src/Controller/Admin/ --dry-run

# 单元测试
./vendor/bin/phpunit packages/wechat-pay-profit-share-bundle/tests/Controller/Admin/
```

---

## 常见问题

### Q: 如何禁用特定操作？

```php
public function configureActions(Actions $actions): Actions
{
    return $actions
        ->disable(Action::NEW, Action::EDIT, Action::DELETE)
    ;
}
```

### Q: 如何添加筛选器？

```php
public function configureFilters(Filters $filters): Filters
{
    return $filters
        ->add(ChoiceFilter::new('state')->setChoices([
            '处理中' => 'PROCESSING',
            '已完成' => 'FINISHED',
        ]))
        ->add(DateTimeFilter::new('createTime'))
    ;
}
```

### Q: 如何格式化金额显示？

```php
yield IntegerField::new('amount', '金额')
    ->formatValue(fn (int $cents) => number_format($cents / 100, 2) . ' 元')
;
```

---

## 参考文档

- [EasyAdmin Bundle 文档](https://symfony.com/bundles/EasyAdminBundle/current/index.html)
- [spec.md](./spec.md) - 功能规格
- [data-model.md](./data-model.md) - 数据模型
- [contracts/](./contracts/) - API 契约文档
