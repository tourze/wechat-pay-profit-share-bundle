# 契约文档：分账订单管理（ProfitShareOrderCrudController）

**Feature**: `admin-dashboard`
**日期**: 2025-11-28
**覆盖需求**: FR-001 ~ FR-012

## 概述

分账订单 CRUD 控制器，提供订单的创建、查看、搜索、筛选功能，以及解冻剩余资金操作。

---

## 路由配置

```php
#[AdminCrud(routePath: '/profit-share/order', routeName: 'profit_share_order')]
```

| 路由名 | 路径 | 方法 | 说明 |
|--------|------|------|------|
| profit_share_order_index | /profit-share/order | GET | 订单列表 |
| profit_share_order_detail | /profit-share/order/{entityId} | GET | 订单详情 |
| profit_share_order_new | /profit-share/order/new | GET/POST | 创建订单 |
| profit_share_order_unfreeze | /profit-share/order/unfreeze/{entityId} | POST | 解冻资金 |
| profit_share_order_return | /profit-share/order/return/{entityId} | GET/POST | 发起回退 |

---

## 接口契约

### 1. 列表页（Index）

**输入**：
- 分页参数：`page`（默认 1）、`perPage`（默认 20，最大 100）
- 搜索：`query`（匹配 outOrderNo, orderId, transactionId, subMchId）
- 筛选：`state`（ProfitShareOrderState 枚举值）
- 排序：`sort[createTime]`（默认 DESC）

**输出字段**：
| 字段 | 展示名 | 说明 |
|------|--------|------|
| id | ID | 主键 |
| outOrderNo | 商户分账单号 | 可搜索 |
| orderId | 微信分账单号 | 可搜索 |
| subMchId | 特约商户号 | 可搜索 |
| transactionId | 微信支付订单号 | 可搜索 |
| state | 分账状态 | 带标签颜色 |
| unfreezeUnsplit | 解冻剩余 | 布尔值 |
| createTime | 创建时间 | 格式：yyyy-MM-dd HH:mm:ss |

**行为约束**：
- 默认按 createTime 倒序
- 不支持批量删除

---

### 2. 详情页（Detail）

**输入**：`entityId`

**输出字段**：
- 所有 Index 字段
- appId, subAppId
- wechatCreatedAt, wechatFinishedAt
- requestPayload（脱敏 JSON）
- responsePayload（脱敏 JSON）
- receivers（关联接收方列表）
- updateTime

**操作按钮**：
| 按钮 | 显示条件 | 动作 |
|------|----------|------|
| 解冻剩余资金 | state == FINISHED && unfreezeUnsplit == false | 跳转解冻表单 |
| 发起回退 | state == FINISHED | 跳转回退表单 |

---

### 3. 创建订单（New）

**表单字段**：

| 字段 | 类型 | 必填 | 校验 |
|------|------|------|------|
| merchant | AssociationField | ✅ | 必须选择商户 |
| subMchId | TextField | ✅ | max:32 |
| transactionId | TextField | ✅ | max:32 |
| outOrderNo | TextField | ✅ | max:64, unique |
| unfreezeUnsplit | BooleanField | ❌ | 默认 false |
| receivers | CollectionField | ✅ | 至少 1 个，每个 amount > 0 |

**接收方子表单字段**：

| 字段 | 类型 | 必填 | 校验 |
|------|------|------|------|
| type | ChoiceField | ✅ | MERCHANT_ID / PERSONAL_OPENID |
| account | TextField | ✅ | max:64 |
| name | TextField | ❌ | max:1024 |
| amount | IntegerField | ✅ | > 0 |
| description | TextField | ✅ | max:80 |

**提交行为**：
1. 前端校验通过后提交
2. 调用 `ProfitShareService::requestProfitShare()`
3. 成功：重定向到详情页，Flash 消息"分账订单创建成功"
4. 失败：返回表单页，Flash 消息"微信 API 错误：{message}"

**错误场景**：
| 错误 | 处理 |
|------|------|
| 校验失败 | 表单显示错误提示 |
| 微信 API 返回错误 | Flash danger 消息，不保存记录 |
| 网络超时 | Flash danger 消息，不保存记录 |

---

### 4. 解冻剩余资金（Unfreeze Action）

**输入**：
| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| entityId | string | ✅ | 订单 ID |
| description | string | ✅ | 解冻描述，max:80 |

**行为**：
1. 从订单获取 merchant, subMchId, transactionId, outOrderNo
2. 构造 `ProfitShareUnfreezeRequest`
3. 调用 `ProfitShareService::unfreezeRemainingAmount()`
4. 成功：重定向到详情页，Flash 消息"资金解冻成功"
5. 失败：重定向到详情页，Flash 消息"解冻失败：{message}"

---

### 5. 发起回退（Return Action）

**输入表单**：
| 字段 | 类型 | 必填 | 校验 |
|------|------|------|------|
| outReturnNo | TextField | ✅ | max:64, unique |
| amount | IntegerField | ✅ | > 0 |
| description | TextField | ✅ | max:80 |

**行为**：
1. 从订单获取 merchant, subMchId, orderId
2. 构造 `ProfitShareReturnRequest`
3. 调用 `ProfitShareReturnService::requestReturn()`
4. 成功：重定向到回退单详情页
5. 失败：返回表单页，Flash 消息"回退失败：{message}"

---

## 测试用例

### 单元测试

```php
class ProfitShareOrderCrudControllerTest extends AdminWebTestCase
{
    public function testIndexPageLoads(): void
    {
        // Given: 数据库中存在分账订单
        // When: 访问列表页
        // Then: 返回 200，页面包含订单数据
    }

    public function testSearchByOutOrderNo(): void
    {
        // Given: 存在商户分账单号为 "TEST001" 的订单
        // When: 搜索 "TEST001"
        // Then: 仅返回该订单
    }

    public function testFilterByState(): void
    {
        // Given: 存在不同状态的订单
        // When: 筛选 state=FINISHED
        // Then: 仅返回已完成订单
    }

    public function testCreateOrderSuccess(): void
    {
        // Given: Mock ProfitShareService 返回成功
        // When: 提交有效表单
        // Then: 重定向到详情页，数据库中存在订单
    }

    public function testCreateOrderApiError(): void
    {
        // Given: Mock ProfitShareService 抛出异常
        // When: 提交有效表单
        // Then: 返回表单页，显示错误消息，数据库无新记录
    }

    public function testUnfreezeSuccess(): void
    {
        // Given: 存在已完成订单，Mock unfreezeRemainingAmount 成功
        // When: 发起解冻
        // Then: 重定向到详情页，显示成功消息
    }

    public function testReturnSuccess(): void
    {
        // Given: 存在已完成订单，Mock requestReturn 成功
        // When: 提交回退表单
        // Then: 重定向到回退单详情页
    }
}
```

---

## 依赖服务

| 服务 | 方法 | 说明 |
|------|------|------|
| ProfitShareService | requestProfitShare | 创建分账订单 |
| ProfitShareService | unfreezeRemainingAmount | 解冻剩余资金 |
| ProfitShareReturnService | requestReturn | 发起回退 |
