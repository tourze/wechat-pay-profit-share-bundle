## 概述

`tourze/wechat-pay-profit-share-bundle` 是一个封装微信支付分账能力的 Symfony
Bundle，覆盖 `/v3/profitsharing/orders` 接口。Bundle 内置 Doctrine 实体保存分账请求
及回执，并提供类型化的 Service/Request 便于业务侧调用。

## 组件说明

- `Entity\ProfitShareOrder` / `Entity\ProfitShareReceiver`：持久化分账单与接收方信息，
  记录请求、响应及时间戳。
- `Entity\ProfitShareReturnOrder` / `Entity\ProfitShareBillTask` / `Entity\ProfitShareOperationLog`：
  记录回退单、账单文件以及所有操作日志。
- `Service\ProfitShareService`：支持请求/查询分账、解冻剩余资金。
- `Service\ProfitShareReturnService`：处理分账回退请求与查询。
- `Service\ProfitShareReceiverService`：添加/删除接收方并自动加密敏感字段。
- `Service\ProfitShareConfigurationService`：查询剩余待分金额与最大分账比例。
- `Service\ProfitShareBillService`：申请账单、校验并下载账单文件。
- `Service\ProfitShareNotificationService`：验签并解密分账动账通知。
- `Request\*` 类型：便于构建各接口的请求参数。

## 使用示例

```php
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareOrderRequest;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReceiverRequest;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareService;
use WechatPayBundle\Entity\Merchant;

$merchant = /* 加载 Merchant 实体 */;
$orderRequest = new ProfitShareOrderRequest(
    subMchId: '1900000109',
    transactionId: '4208450740201411110007820472',
    outOrderNo: 'P20150806125346',
);
$orderRequest->setUnfreezeUnsplit(true);
$orderRequest->addReceiver(new ProfitShareReceiverRequest(
    type: 'MERCHANT_ID',
    account: '1900000109',
    amount: 100,
    description: '分给商户1900000109'
));

$profitShareOrder = $profitShareService->requestProfitShare($merchant, $orderRequest);

// 查询分账 / 解冻剩余资金
$profitShareOrder = $profitShareService->queryProfitShareOrder(
    $merchant,
    '1900000109',
    'P20150806125346',
    '4208450740201411110007820472'
);
$profitShareService->unfreezeRemainingAmount($merchant, new ProfitShareUnfreezeRequest(
    subMchId: '1900000109',
    transactionId: '4208450740201411110007820472',
    outOrderNo: 'P20150806125346',
    description: '解冻剩余资金',
));

// 发起回退与查询
$returnService = new ProfitShareReturnService($returnRepository, $operationLogRepository, $wechatPayBuilder, $logger);
$returnOrder = $returnService->requestReturn($merchant, new ProfitShareReturnRequest(
    subMchId: '1900000109',
    outReturnNo: 'R20150806125346',
    amount: 100,
    description: '回退说明',
    orderId: $profitShareOrder->getOrderId(),
));

// 接收方管理
$receiverService = new ProfitShareReceiverService($operationLogRepository, $wechatPayBuilder, $logger);
$receiverService->addReceiver($merchant, new ProfitShareReceiverAddRequest(
    subMchId: '1900000109',
    appid: 'wx123',
    type: 'MERCHANT_ID',
    account: '1900000109',
    relationType: 'SERVICE_PROVIDER',
    name: '测试商户',
));

// 申请/下载账单
$billService = new ProfitShareBillService($billTaskRepository, $operationLogRepository, $wechatPayBuilder, $logger);
$billTask = $billService->applyBill($merchant, new ProfitShareBillRequest(new \DateTimeImmutable('2025-01-20')));
$billService->downloadBill($merchant, $billTask, new ProfitShareBillDownloadRequest(
    downloadUrl: $billTask->getDownloadUrl(),
    localPath: '/tmp/profitshare.csv',
));

// 解密分账动账通知
$notificationService = new ProfitShareNotificationService($operationLogRepository, $wechatPayBuilder, $logger);
$result = $notificationService->handleNotification($merchant, $body, $headers);
```

微信返回的 `order_id`、状态、接收方明细会自动写回实体，方便后续查询与对账。

## 命令行工具

此 Bundle 提供以下控制台命令，用于后台任务执行：

### 下载分账账单
```bash
php bin/console wechat:profit-share:download-bill [options]
```

**选项：**
- `--dry-run`：模拟执行，不实际下载数据
- `--expire-days=DAYS`：账单过期天数（默认 7）
- `--download-path=PATH`：下载存储路径（默认 `/var/data/wechat-profit-share-bills`）
- `--merchant-id=ID`：指定商户ID，不指定则处理所有商户

**示例：**
```bash
# 下载所有就绪的账单

[English](README.md) | [中文](README.zh-CN.md)
php bin/console wechat:profit-share:download-bill

# 模拟执行，不实际下载
php bin/console wechat:profit-share:download-bill --dry-run

# 指定下载路径和商户
php bin/console wechat:profit-share:download-bill --download-path=/mnt/bills --merchant-id=1900000109
```

### 重试失败的分账接收方
```bash
php bin/console wechat:profit-share:retry [options]
```

**选项：**
- `--dry-run`：模拟执行
- `--merchant-id=ID`：指定商户ID（可选）
- `--max-retries=N`：最大重试次数

### 同步分账订单状态
```bash
php bin/console wechat:profit-share:sync [options]
```

**选项：**
- `--dry-run`：模拟执行
- `--days=N`：同步近 N 天的订单（默认 1）

### 解冻剩余资金
```bash
php bin/console wechat:profit-share:unfreeze [options]
```

**选项：**
- `--dry-run`：模拟执行
- `--force`：强制解冻所有待解冻订单

## 测试

```bash
vendor/bin/phpunit packages/wechat-pay-profit-share-bundle/tests
```
