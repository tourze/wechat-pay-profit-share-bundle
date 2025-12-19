# 数据模型：微信支付分账后台管理界面

**Feature**: `admin-dashboard`
**日期**: 2025-11-28

> 说明：本文档描述后台管理界面涉及的实体及其在 Admin 界面中的展示/操作映射。实体已存在于 `src/Entity/`，无需新建。

## 实体概览

```
┌─────────────────────┐      1:N      ┌─────────────────────┐
│  ProfitShareOrder   │──────────────▶│ ProfitShareReceiver │
│  (分账订单)          │               │  (分账接收方)        │
└─────────────────────┘               └─────────────────────┘
         │
         │ 1:N (逻辑关联，通过 orderId/outOrderNo)
         ▼
┌─────────────────────┐
│ProfitShareReturnOrder│
│  (分账回退单)        │
└─────────────────────┘

┌─────────────────────┐               ┌─────────────────────┐
│ ProfitShareBillTask │               │ProfitShareOperationLog│
│  (账单任务)          │               │  (操作日志)          │
└─────────────────────┘               └─────────────────────┘
```

---

## 实体详情

### ProfitShareOrder（分账订单）

**用途**：存储分账请求与响应状态，是后台管理的核心实体。

| 字段 | 类型 | 说明 | Admin 展示 |
|------|------|------|------------|
| id | string (snowflake) | 主键 | Index, Detail |
| merchant | Merchant (关联) | 商户 | Index (选择器), Detail |
| subMchId | string(32) | 特约商户号 | Index, Detail, Form |
| appId | string(32)? | 公众账号ID | Detail |
| subAppId | string(32)? | 特约商户公众账号ID | Detail |
| transactionId | string(32) | 微信支付订单号 | Index, Detail, Form |
| outOrderNo | string(64) | 商户分账单号 | Index, Detail, Form |
| orderId | string(64)? | 微信分账单号 | Index, Detail |
| state | ProfitShareOrderState | 分账状态 | Index (筛选), Detail |
| unfreezeUnsplit | bool | 是否解冻剩余资金 | Index, Detail, Form |
| requestPayload | text? | 请求 JSON | Detail (脱敏) |
| responsePayload | text? | 响应 JSON | Detail (脱敏) |
| wechatCreatedAt | datetime? | 微信创建时间 | Detail |
| wechatFinishedAt | datetime? | 微信完成时间 | Detail |
| createTime | datetime | 创建时间 | Index, Detail |
| updateTime | datetime | 更新时间 | Detail |

**状态枚举 ProfitShareOrderState**：
- `PROCESSING` - 处理中
- `FINISHED` - 已完成

**Admin 操作**：
- 创建（FR-001~005）
- 查看列表/详情（FR-006~009）
- 解冻剩余资金（FR-010~012）
- 发起回退（FR-015~019）

---

### ProfitShareReceiver（分账接收方）

**用途**：存储每笔分账的接收方信息，与 ProfitShareOrder 为多对一关系。

| 字段 | 类型 | 说明 | Admin 展示 |
|------|------|------|------------|
| id | string (snowflake) | 主键 | Index |
| order | ProfitShareOrder | 关联订单 | Detail (跳转) |
| sequence | int | 接收方顺序 | Detail |
| type | string(32) | 接收方类型 | Index, Detail |
| account | string(64) | 接收方账号 | Index, Detail |
| name | string(1024)? | 接收方姓名（密文） | Detail (脱敏) |
| amount | int | 分账金额（分） | Index, Detail |
| description | string(80) | 分账描述 | Detail |
| result | ProfitShareReceiverResult | 分账结果 | Index (筛选), Detail |
| failReason | string(64)? | 分账失败原因 | Detail |
| detailId | string(64)? | 微信分账明细单号 | Detail |
| retryCount | int | 重试次数 | Detail |
| wechatCreatedAt | datetime? | 微信创建时间 | Detail |
| wechatFinishedAt | datetime? | 微信完成时间 | Detail |

**结果枚举 ProfitShareReceiverResult**：
- `PENDING` - 待处理
- `SUCCESS` - 成功
- `FAILED` - 失败

**Admin 操作**：
- 在订单详情中展示列表（FR-013）
- 按结果筛选（FR-014）

---

### ProfitShareReturnOrder（分账回退单）

**用途**：存储分账回退请求与结果。

| 字段 | 类型 | 说明 | Admin 展示 |
|------|------|------|------------|
| id | string (snowflake) | 主键 | Index |
| merchant | Merchant (关联) | 商户 | Detail |
| subMchId | string(32) | 特约商户号 | Index, Detail |
| orderId | string(64)? | 微信分账单号 | Detail |
| outOrderNo | string(64)? | 商户分账单号 | Index, Detail |
| outReturnNo | string(64) | 商户回退单号 | Index, Detail, Form |
| returnNo | string(64)? | 微信回退单号 | Index, Detail |
| amount | int | 回退金额（分） | Index, Detail, Form |
| description | string(80)? | 回退描述 | Detail, Form |
| result | string(20)? | 回退结果 | Index (筛选), Detail |
| failReason | string(64)? | 失败原因 | Detail |
| wechatCreatedAt | datetime? | 微信创建时间 | Detail |
| wechatFinishedAt | datetime? | 微信完成时间 | Detail |
| requestPayload | text? | 请求负载 | Detail (脱敏) |
| responsePayload | text? | 响应负载 | Detail (脱敏) |
| createTime | datetime | 创建时间 | Index, Detail |

**Admin 操作**：
- 查看列表/详情（FR-020~021）
- 创建（通过订单详情页发起，FR-015~019）

---

### ProfitShareBillTask（账单任务）

**用途**：存储账单下载任务状态和进度。

| 字段 | 类型 | 说明 | Admin 展示 |
|------|------|------|------------|
| id | string (snowflake) | 主键 | Index |
| merchant | Merchant (关联) | 商户 | Detail |
| subMchId | string(32)? | 特约商户号 | Index, Detail |
| billDate | date | 账单日期 | Index, Detail, Form |
| tarType | string(10)? | 压缩类型 | Detail |
| hashType | string(10)? | 哈希类型 | Detail |
| hashValue | string(1024)? | 哈希值 | Detail |
| downloadUrl | string(2048)? | 下载地址 | Detail |
| status | ProfitShareBillStatus | 状态 | Index (筛选), Detail |
| downloadedAt | datetime? | 下载时间 | Index, Detail |
| localPath | string(255)? | 本地存储路径 | Detail |
| requestPayload | text? | 请求负载 | Detail |
| responsePayload | text? | 响应负载 | Detail |
| createTime | datetime | 创建时间 | Index, Detail |

**状态枚举 ProfitShareBillStatus**：
- `PENDING` - 待处理
- `READY` - 就绪
- `DOWNLOADING` - 下载中
- `DOWNLOADED` - 已下载
- `FAILED` - 失败
- `EXPIRED` - 过期

**Admin 操作**：
- 查看列表（FR-025~026）
- 申请账单（FR-022~024）

---

### ProfitShareOperationLog（操作日志）

**用途**：记录所有分账相关 API 操作，便于审计和问题排查。

| 字段 | 类型 | 说明 | Admin 展示 |
|------|------|------|------------|
| id | string (snowflake) | 主键 | Index |
| merchant | Merchant (关联) | 商户 | Detail |
| subMchId | string(32)? | 特约商户号 | Index, Detail |
| type | ProfitShareOperationType | 操作类型 | Index (筛选), Detail |
| success | bool | 是否成功 | Index (筛选), Detail |
| errorCode | string(32)? | 错误码 | Index, Detail |
| errorMessage | string(255)? | 错误信息 | Index, Detail |
| requestPayload | text? | 请求负载 | Detail |
| responsePayload | text? | 响应负载 | Detail |
| createTime | datetime | 创建时间 | Index, Detail |

**操作类型枚举 ProfitShareOperationType**：
- `REQUEST_ORDER` - 请求分账
- `QUERY_ORDER` - 查询分账
- `UNFREEZE` - 解冻资金
- `REQUEST_RETURN` - 请求回退
- `QUERY_RETURN` - 查询回退
- `ADD_RECEIVER` - 添加接收方
- `DELETE_RECEIVER` - 删除接收方
- `APPLY_BILL` - 申请账单
- `DOWNLOAD_BILL` - 下载账单
- `NOTIFICATION` - 通知处理

**Admin 操作**：
- 查看列表/详情（FR-027~029）
- 按操作类型/成功状态筛选（FR-028）

---

## 表单数据结构（创建分账订单）

创建分账订单时使用的临时数据结构（非持久化实体）：

```php
// ProfitShareOrderRequest（已存在于 src/Request/）
class ProfitShareOrderRequest
{
    public string $subMchId;           // 特约商户号
    public string $transactionId;      // 微信支付订单号
    public string $outOrderNo;         // 商户分账单号
    public bool $unfreezeUnsplit;      // 是否解冻剩余资金
    /** @var ProfitShareReceiverRequest[] */
    public array $receivers = [];      // 接收方列表
}

// ProfitShareReceiverRequest（已存在于 src/Request/）
class ProfitShareReceiverRequest
{
    public string $type;        // MERCHANT_ID | PERSONAL_OPENID
    public string $account;     // 接收方账号
    public ?string $name;       // 接收方姓名（可选）
    public int $amount;         // 分账金额（分）
    public string $description; // 分账描述
}
```

---

## 校验规则摘要

| 场景 | 字段 | 规则 |
|------|------|------|
| 创建分账订单 | receivers | 非空（至少 1 个接收方） |
| 创建分账订单 | receivers[].amount | > 0 |
| 发起回退 | amount | > 0 |
| 申请账单 | billDate | 有效日期 |

> 注：仅基础校验，复杂业务校验（如金额不超过可分账余额）由微信 API 返回错误处理。
