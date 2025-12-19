# 契约文档：分账账单任务管理（ProfitShareBillTaskCrudController）

**Feature**: `admin-dashboard`
**日期**: 2025-11-28
**覆盖需求**: FR-022 ~ FR-026

## 概述

分账账单任务 CRUD 控制器，提供账单任务的查看、筛选功能，以及申请账单操作。

---

## 路由配置

```php
#[AdminCrud(routePath: '/profit-share/bill', routeName: 'profit_share_bill')]
```

| 路由名 | 路径 | 方法 | 说明 |
|--------|------|------|------|
| profit_share_bill_index | /profit-share/bill | GET | 账单任务列表 |
| profit_share_bill_detail | /profit-share/bill/{entityId} | GET | 任务详情 |
| profit_share_bill_apply | /profit-share/bill/apply | GET/POST | 申请账单 |

---

## 接口契约

### 1. 列表页（Index）

**输入**：
- 分页参数：`page`（默认 1）、`perPage`（默认 20，最大 100）
- 筛选：`status`（ProfitShareBillStatus 枚举值）、`billDate`（日期范围）
- 排序：`sort[createTime]`（默认 DESC）

**输出字段**：
| 字段 | 展示名 | 说明 |
|------|--------|------|
| id | ID | 主键 |
| billDate | 账单日期 | 格式：yyyy-MM-dd |
| subMchId | 特约商户号 | |
| status | 状态 | 带标签颜色 |
| downloadUrl | 下载地址 | 仅 READY/DOWNLOADED 显示 |
| downloadedAt | 下载时间 | 仅 DOWNLOADED 显示 |
| createTime | 创建时间 | 格式：yyyy-MM-dd HH:mm:ss |

**行为约束**：
- 默认按 createTime 倒序
- 禁用编辑、删除操作

---

### 2. 详情页（Detail）

**输入**：`entityId`

**输出字段**：
- 所有 Index 字段
- merchant（关联商户）
- tarType（压缩类型）
- hashType, hashValue（哈希信息）
- localPath（本地存储路径）
- requestPayload（请求负载）
- responsePayload（响应负载）
- updateTime

**操作按钮**：无（只读）

---

### 3. 申请账单（Apply Action）

**表单字段**：

| 字段 | 类型 | 必填 | 校验 |
|------|------|------|------|
| merchant | AssociationField | ✅ | 必须选择商户 |
| billDate | DateField | ✅ | 有效日期，不能超过当前日期 |

**提交行为**：
1. 前端校验通过后提交
2. 调用 `ProfitShareBillService::applyBill()`
3. 成功：重定向到列表页，Flash 消息"账单申请已提交"
4. 失败：返回表单页，Flash 消息"账单申请失败：{message}"

**错误场景**：
| 错误 | 处理 |
|------|------|
| 日期无效 | 表单显示错误提示 |
| 微信 API 返回错误 | Flash danger 消息 |
| 同一日期已存在任务 | 提示"该日期账单任务已存在" |

---

## 筛选器配置

| 筛选器 | 类型 | 选项 |
|--------|------|------|
| status | ChoiceFilter | PENDING / READY / DOWNLOADING / DOWNLOADED / FAILED / EXPIRED |
| billDate | DateFilter | 日期范围 |
| subMchId | TextFilter | 文本匹配 |

---

## 状态标签颜色

| 状态 | 颜色 |
|------|------|
| PENDING | 灰色 |
| READY | 蓝色 |
| DOWNLOADING | 黄色 |
| DOWNLOADED | 绿色 |
| FAILED | 红色 |
| EXPIRED | 橙色 |

---

## 测试用例

### 单元测试

```php
class ProfitShareBillTaskCrudControllerTest extends AdminWebTestCase
{
    public function testIndexPageLoads(): void
    {
        // Given: 数据库中存在账单任务
        // When: 访问列表页
        // Then: 返回 200，页面包含任务数据
    }

    public function testFilterByStatus(): void
    {
        // Given: 存在不同状态的任务
        // When: 筛选 status=DOWNLOADED
        // Then: 仅返回已下载的任务
    }

    public function testFilterByDateRange(): void
    {
        // Given: 存在不同日期的任务
        // When: 筛选 billDate 范围 2025-01-01 ~ 2025-01-31
        // Then: 仅返回该范围内的任务
    }

    public function testApplyBillSuccess(): void
    {
        // Given: Mock ProfitShareBillService 返回成功
        // When: 提交有效申请表单
        // Then: 重定向到列表页，数据库中存在新任务
    }

    public function testApplyBillApiError(): void
    {
        // Given: Mock ProfitShareBillService 抛出异常
        // When: 提交有效申请表单
        // Then: 返回表单页，显示错误消息
    }

    public function testApplyBillDuplicateDate(): void
    {
        // Given: 该日期已存在账单任务
        // When: 再次申请同一日期
        // Then: 显示"该日期账单任务已存在"
    }
}
```

---

## 依赖服务

| 服务 | 方法 | 说明 |
|------|------|------|
| ProfitShareBillService | applyBill | 申请账单 |
