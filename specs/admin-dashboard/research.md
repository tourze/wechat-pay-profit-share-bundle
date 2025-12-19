# 技术研究：微信支付分账后台管理界面

**Feature**: `admin-dashboard`
**日期**: 2025-11-28

## 研究任务清单

| 编号 | 研究主题 | 状态 |
|------|----------|------|
| R-001 | EasyAdmin 自定义操作（Action）实现模式 | ✅ 完成 |
| R-002 | EasyAdmin 表单中嵌入动态集合字段 | ✅ 完成 |
| R-003 | 现有 Service 层 API 调用模式 | ✅ 完成 |
| R-004 | 敏感信息脱敏展示方案 | ✅ 完成 |

---

## R-001: EasyAdmin 自定义操作（Action）实现模式

### 决策

使用 EasyAdmin 的 `Action::new()` 结合自定义 Controller 方法实现写操作（创建分账、解冻资金、发起回退、申请账单）。

### 理由

1. **原生支持**：EasyAdmin 4.x 提供完整的 Action 机制，支持在 Index/Detail 页面添加自定义按钮
2. **代码组织**：操作逻辑集中在 CrudController 中，便于维护
3. **现有模式**：项目中已有类似实现（如 `CombinePayOrderCrudController`）

### 实现模式

```php
// 配置 Actions
public function configureActions(Actions $actions): Actions
{
    $unfreezeAction = Action::new('unfreeze', '解冻剩余资金')
        ->linkToCrudAction('unfreezeRemainingAmount')
        ->displayIf(fn (ProfitShareOrder $order) => $order->getState() === ProfitShareOrderState::FINISHED);

    return $actions
        ->add(Crud::PAGE_DETAIL, $unfreezeAction)
        ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ->disable(Action::DELETE)
    ;
}

// 自定义操作方法
#[AdminAction(routePath: '/unfreeze/{entityId}', routeName: 'profit_share_order_unfreeze')]
public function unfreezeRemainingAmount(AdminContext $context): Response
{
    // 获取实体、调用 Service、处理结果
}
```

### 备选方案

- **独立 Controller**：创建非 EasyAdmin Controller 处理写操作 → 放弃，因增加路由复杂度且失去 Admin 上下文
- **Modal 表单**：使用 JavaScript Modal 提交 → 放弃，因需要额外前端代码且不符合 EasyAdmin 规范

---

## R-002: EasyAdmin 表单中嵌入动态集合字段

### 决策

使用 EasyAdmin 的 `CollectionField` 结合自定义 FormType 实现分账接收方的动态添加。

### 理由

1. **原生组件**：EasyAdmin 的 CollectionField 支持动态添加/删除子表单
2. **类型安全**：通过自定义 FormType 可强制字段校验（类型、账号、金额 > 0）
3. **用户体验**：无需页面跳转，在同一表单中完成所有输入

### 实现模式

```php
// 在 CrudController 中
yield CollectionField::new('receivers', '分账接收方')
    ->setEntryType(ProfitShareReceiverFormType::class)
    ->allowAdd()
    ->allowDelete()
    ->setRequired(true)
    ->setHelp('至少添加一个接收方')
;

// 自定义 FormType
class ProfitShareReceiverFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, ['choices' => ['MERCHANT_ID' => 'MERCHANT_ID', 'PERSONAL_OPENID' => 'PERSONAL_OPENID']])
            ->add('account', TextType::class)
            ->add('name', TextType::class, ['required' => false])
            ->add('amount', IntegerType::class, ['constraints' => [new Positive()]])
            ->add('description', TextType::class)
        ;
    }
}
```

### 备选方案

- **关联实体表单**：使用 AssociationField → 放弃，因创建时接收方尚不存在于数据库
- **JSON 字段**：使用 ArrayField 存储接收方数组 → 放弃，因缺乏类型校验

---

## R-003: 现有 Service 层 API 调用模式

### 决策

直接注入并调用现有 Service 类（`ProfitShareService`、`ProfitShareReturnService`、`ProfitShareBillService`）处理微信 API 交互。

### 理由

1. **复用性**：Service 层已封装所有微信 API 调用逻辑，包括签名、加密、错误处理
2. **一致性**：后台操作与 API/CLI 调用使用相同业务逻辑
3. **可测试性**：Service 可 Mock，便于 Controller 单元测试

### 现有 Service 方法映射

| 功能 | Service 方法 | 输入 | 输出 |
|------|-------------|------|------|
| 创建分账订单 | `ProfitShareService::requestProfitShare` | Merchant, ProfitShareOrderRequest | ProfitShareOrder |
| 解冻剩余资金 | `ProfitShareService::unfreezeRemainingAmount` | Merchant, ProfitShareUnfreezeRequest | void |
| 发起回退 | `ProfitShareReturnService::requestReturn` | Merchant, ProfitShareReturnRequest | ProfitShareReturnOrder |
| 申请账单 | `ProfitShareBillService::applyBill` | Merchant, ProfitShareBillRequest | ProfitShareBillTask |

### 错误处理模式

```php
try {
    $order = $this->profitShareService->requestProfitShare($merchant, $request);
    $this->addFlash('success', '分账订单创建成功');
    return $this->redirectToRoute('admin_profit_share_order_detail', ['entityId' => $order->getId()]);
} catch (WechatPayException $e) {
    $this->addFlash('danger', '微信 API 错误：' . $e->getMessage());
    return $this->redirect($this->generateUrl('admin_profit_share_order_new'));
}
```

---

## R-004: 敏感信息脱敏展示方案

### 决策

使用自定义 Field 格式化函数实现敏感信息脱敏，敏感字段包括：接收方姓名、请求/响应负载中的证书信息。

### 理由

1. **安全合规**：敏感信息不应在后台界面明文展示
2. **实现简单**：EasyAdmin Field 的 `formatValue` 回调可轻松实现
3. **可配置**：脱敏规则可通过 Service 或配置文件统一管理

### 实现模式

```php
// 姓名脱敏（保留首尾字符）
yield TextField::new('name', '接收方姓名')
    ->formatValue(fn (?string $value) => $this->maskName($value))
;

private function maskName(?string $name): string
{
    if (null === $name || '' === $name) {
        return '未设置';
    }
    $length = mb_strlen($name);
    if ($length <= 2) {
        return mb_substr($name, 0, 1) . '*';
    }
    return mb_substr($name, 0, 1) . str_repeat('*', $length - 2) . mb_substr($name, -1);
}

// JSON 负载脱敏（移除敏感字段后格式化）
yield TextareaField::new('requestPayload', '请求负载')
    ->formatValue(fn (?string $json) => $this->formatAndMaskJson($json, ['certificate', 'serial_no']))
;
```

### 备选方案

- **后端完全隐藏**：敏感字段不返回给前端 → 放弃，因调试时需要查看完整数据
- **前端遮罩**：JavaScript 动态遮罩 → 放弃，因数据已暴露给前端

---

## 总结

所有研究任务已完成，技术方案明确：

1. **写操作**：通过 EasyAdmin Action + 自定义 Controller 方法实现
2. **动态表单**：使用 CollectionField + 自定义 FormType 实现接收方添加
3. **业务逻辑**：复用现有 Service 层，保持一致性
4. **安全展示**：使用 Field 格式化函数实现脱敏

无未解决的 NEEDS CLARIFICATION 项。
