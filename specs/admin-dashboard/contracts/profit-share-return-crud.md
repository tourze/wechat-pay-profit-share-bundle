# 契约文档：分账回退单管理（ProfitShareReturnOrderCrudController）

**Feature**: `admin-dashboard`
**日期**: 2025-11-28
**覆盖需求**: FR-020 ~ FR-021

## 概述

分账回退单 CRUD 控制器，提供回退单的查看、搜索、筛选功能。回退单的创建通过订单详情页的"发起回退"操作完成（见 profit-share-order-crud.md）。

---

## 路由配置

```php
#[AdminCrud(routePath: '/profit-share/return', routeName: 'profit_share_return')]
```

| 路由名 | 路径 | 方法 | 说明 |
|--------|------|------|------|
| profit_share_return_index | /profit-share/return | GET | 回退单列表 |
| profit_share_return_detail | /profit-share/return/{entityId} | GET | 回退单详情 |

---

## 接口契约

### 1. 列表页（Index）

**输入**：
- 分页参数：`page`（默认 1）、`perPage`（默认 20，最大 100）
- 搜索：`query`（匹配 outReturnNo, returnNo, outOrderNo, subMchId）
- 筛选：`result`（回退结果）
- 排序：`sort[createTime]`（默认 DESC）

**输出字段**：
| 字段 | 展示名 | 说明 |
|------|--------|------|
| id | ID | 主键 |
| outReturnNo | 商户回退单号 | 可搜索 |
| returnNo | 微信回退单号 | 可搜索 |
| outOrderNo | 关联分账单号 | 可搜索，链接到订单 |
| subMchId | 特约商户号 | 可搜索 |
| amount | 回退金额 | 格式：X.XX 元 |
| result | 回退结果 | 带标签颜色 |
| createTime | 创建时间 | 格式：yyyy-MM-dd HH:mm:ss |

**行为约束**：
- 默认按 createTime 倒序
- 禁用新建、编辑、删除操作（只读列表）

---

### 2. 详情页（Detail）

**输入**：`entityId`

**输出字段**：
- 所有 Index 字段
- merchant（关联商户）
- orderId（微信分账单号）
- description（回退描述）
- failReason（失败原因，仅失败时显示）
- wechatCreatedAt, wechatFinishedAt
- requestPayload（脱敏 JSON）
- responsePayload（脱敏 JSON）
- updateTime

**操作按钮**：无（只读）

---

## 筛选器配置

| 筛选器 | 类型 | 选项 |
|--------|------|------|
| result | ChoiceFilter | PROCESSING / SUCCESS / FAILED |
| subMchId | TextFilter | 文本匹配 |
| createTime | DateTimeFilter | 日期范围 |

---

## 测试用例

### 单元测试

```php
class ProfitShareReturnOrderCrudControllerTest extends AdminWebTestCase
{
    public function testIndexPageLoads(): void
    {
        // Given: 数据库中存在回退单
        // When: 访问列表页
        // Then: 返回 200，页面包含回退单数据
    }

    public function testSearchByOutReturnNo(): void
    {
        // Given: 存在商户回退单号为 "R001" 的回退单
        // When: 搜索 "R001"
        // Then: 仅返回该回退单
    }

    public function testFilterByResult(): void
    {
        // Given: 存在不同结果的回退单
        // When: 筛选 result=SUCCESS
        // Then: 仅返回成功的回退单
    }

    public function testDetailPageShowsFailReason(): void
    {
        // Given: 存在失败的回退单
        // When: 访问详情页
        // Then: 页面显示失败原因字段
    }

    public function testNewActionDisabled(): void
    {
        // Given: 用户访问新建页面
        // When: 尝试访问 /profit-share/return/new
        // Then: 返回 403 或重定向到列表页
    }
}
```

---

## 依赖服务

本控制器仅提供查看功能，不直接调用业务 Service。回退单创建由 `ProfitShareOrderCrudController::returnAction` 处理。
