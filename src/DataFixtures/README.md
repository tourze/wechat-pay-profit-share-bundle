# 微信支付分账包 DataFixtures

本目录包含微信支付分账功能的数据填充类，用于在开发和测试环境中创建测试数据。

## 可用的DataFixtures类

### 1. ProfitShareBillTaskFixtures
创建分账账单任务测试数据，包含各种状态：
- 待生成账单 (PENDING)
- 可下载账单 (READY)
- 已下载账单 (DOWNLOADED)
- 失败账单 (FAILED)

### 2. ProfitShareOperationLogFixtures
创建分账操作日志测试数据，包含各种操作类型：
- 分账请求 (REQUEST_ORDER)
- 添加接收方 (ADD_RECEIVER)
- 申请账单 (APPLY_BILL)
- 分账查询 (QUERY_ORDER)
- 分账通知 (NOTIFICATION)

### 3. ProfitShareOrderFixtures
创建分账订单测试数据，包含各种状态：
- 处理中订单 (PROCESSING)
- 已完成订单 (FINISHED)
- 已关闭订单 (CLOSED)

### 4. ProfitShareReceiverFixtures
创建分账接收方测试数据，与订单关联：
- 待处理接收方 (PENDING)
- 成功接收方 (SUCCESS)
- 失败接收方 (FAILED)
- 需要重试接收方 (RETRY)
- 已关闭接收方 (CLOSED)

### 5. ProfitShareReturnOrderFixtures
创建分账回退单测试数据，包含各种状态：
- 处理中回退 (PROCESSING)
- 成功回退 (SUCCESS)
- 失败回退 (FAILED)
- 部分成功回退 (SUCCESS with fail_reason)

## 使用方法

### 加载所有测试数据
```bash
php bin/console doctrine:fixtures:load --group=test --group=dev
```

### 加载特定类型的测试数据
```bash
# 只加载分账订单数据
php bin/console doctrine:fixtures:load --group=test --append --fixtures=Tourze\WechatPayProfitShareBundle\DataFixtures\ProfitShareOrderFixtures

# 只加载操作日志数据
php bin/console doctrine:fixtures:load --group=test --append --fixtures=Tourze\WechatPayProfitShareBundle\DataFixtures\ProfitShareOperationLogFixtures
```

## 依赖关系

这些DataFixtures依赖于：
- `WechatPayBundle\DataFixtures\MerchantFixtures` 中的 `test-merchant` 引用
- 相应的Entity类和枚举类
- Doctrine Fixtures Bundle

## 数据特点

- **真实性**：使用模拟的真实微信支付数据格式
- **完整性**：覆盖所有主要业务场景和状态
- **关联性**：订单与接收方之间存在正确的关联关系
- **时序性**：包含合理的时间戳和业务流程时间
- **多样性**：包含各种错误场景和边界条件

## 注意事项

- 仅在 `test` 和 `dev` 环境中可用
- 使用 `--append` 参数可以避免清空现有数据
- 建议在单元测试和集成测试中使用这些测试数据