## Overview

`tourze/wechat-pay-profit-share-bundle` provides a Symfony bundle that encapsulates the
WeChat Pay partner profit sharing API (`/v3/profitsharing/orders`). The bundle offers
Doctrine entities for persisting profit share orders/receivers and a service for
invoking WeChat Pay with typed request objects.

## Key Components

- `Entity\ProfitShareOrder` / `Entity\ProfitShareReceiver`: Store request payloads and
  response states from WeChat Pay.
- `Entity\ProfitShareReturnOrder` / `Entity\ProfitShareBillTask` / `Entity\ProfitShareOperationLog`:
  Persist回退、账单与操作日志数据，确保审计可追踪。
- `Service\ProfitShareService`: 请求/查询分账单、解冻剩余资金。
- `Service\ProfitShareReturnService`: 请求/查询分账回退。
- `Service\ProfitShareReceiverService`: 添加/删除分账接收方，自动加密敏感字段。
- `Service\ProfitShareConfigurationService`: 查询剩余待分金额、最大分账比例。
- `Service\ProfitShareBillService`: 申请分账账单并下载校验。
- `Service\ProfitShareNotificationService`: 验签并解密微信分账动账通知。
- `Request\*` 类型：用于描述各接口请求参数。

## Usage

```php
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareOrderRequest;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReceiverRequest;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareService;
use WechatPayBundle\Entity\Merchant;

$merchant = /* load Merchant entity */;
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

// Query existing order / unfreeze
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

// Request return & query result
$returnService = new ProfitShareReturnService($returnRepository, $operationLogRepository, $wechatPayBuilder, $logger);
$returnOrder = $returnService->requestReturn($merchant, new ProfitShareReturnRequest(
    subMchId: '1900000109',
    outReturnNo: 'R20150806125346',
    amount: 100,
    description: '回退说明',
    orderId: $profitShareOrder->getOrderId(),
));

// Add / delete receiver
$receiverService = new ProfitShareReceiverService($operationLogRepository, $wechatPayBuilder, $logger);
$receiverService->addReceiver($merchant, new ProfitShareReceiverAddRequest(
    subMchId: '1900000109',
    appid: 'wx123',
    type: 'MERCHANT_ID',
    account: '1900000109',
    relationType: 'SERVICE_PROVIDER',
    name: '测试商户',
));

// Apply & download bill
$billService = new ProfitShareBillService($billTaskRepository, $operationLogRepository, $wechatPayBuilder, $logger);
$billTask = $billService->applyBill($merchant, new ProfitShareBillRequest(new \DateTimeImmutable('2025-01-20')));
$billService->downloadBill($merchant, $billTask, new ProfitShareBillDownloadRequest(
    downloadUrl: $billTask->getDownloadUrl(),
    localPath: '/tmp/profitshare.csv',
));

// Decrypt notification
$notificationService = new ProfitShareNotificationService($operationLogRepository, $wechatPayBuilder, $logger);
$result = $notificationService->handleNotification($merchant, $body, $headers);
```

Responses from WeChat Pay are synchronized into the `ProfitShareOrder` and related
`ProfitShareReceiver` entities, including state, detail IDs, and timestamps.

## Testing

Run bundle-specific tests:

```bash
vendor/bin/phpunit packages/wechat-pay-profit-share-bundle/tests
```
