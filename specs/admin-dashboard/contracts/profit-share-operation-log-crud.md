# 契约文档：分账操作日志管理（ProfitShareOperationLogCrudController）

**Feature**: `admin-dashboard`
**日期**: 2025-11-28
**覆盖需求**: FR-027 ~ FR-029

## 概述

分账操作日志 CRUD 控制器，提供操作日志的查看、筛选功能。纯只读界面，用于审计和问题排查。

---

## 路由配置

```php
#[AdminCrud(routePath: '/profit-share/log', routeName: 'profit_share_log')]
```

| 路由名 | 路径 | 方法 | 说明 |
|--------|------|------|------|
| profit_share_log_index | /profit-share/log | GET | 日志列表 |
| profit_share_log_detail | /profit-share/log/{entityId} | GET | 日志详情 |

---

## 接口契约

### 1. 列表页（Index）

**输入**：
- 分页参数：`page`（默认 1）、`perPage`（默认 20，最大 100）
- 筛选：`type`（ProfitShareOperationType 枚举值）、`success`（布尔值）
- 排序：`sort[createTime]`（默认 DESC）

**输出字段**：
| 字段 | 展示名 | 说明 |
|------|--------|------|
| id | ID | 主键 |
| type | 操作类型 | 带标签 |
| subMchId | 特约商户号 | |
| success | 是否成功 | 布尔值图标 |
| errorCode | 错误码 | 仅失败时显示 |
| errorMessage | 错误信息 | 仅失败时显示，截断 |
| createTime | 创建时间 | 格式：yyyy-MM-dd HH:mm:ss |

**行为约束**：
- 默认按 createTime 倒序
- 禁用新建、编辑、删除操作（纯只读）

---

### 2. 详情页（Detail）

**输入**：`entityId`

**输出字段**：
- 所有 Index 字段（errorMessage 完整显示）
- merchant（关联商户）
- requestPayload（完整 JSON，格式化展示）
- responsePayload（完整 JSON，格式化展示）
- updateTime

**操作按钮**：无（只读）

---

## 筛选器配置

| 筛选器 | 类型 | 选项 |
|--------|------|------|
| type | ChoiceFilter | 所有 ProfitShareOperationType 枚举值 |
| success | BooleanFilter | 是 / 否 |
| subMchId | TextFilter | 文本匹配 |
| createTime | DateTimeFilter | 日期范围 |

---

## 操作类型标签

| 类型 | 展示名 | 颜色 |
|------|--------|------|
| REQUEST_ORDER | 请求分账 | 蓝色 |
| QUERY_ORDER | 查询分账 | 灰色 |
| UNFREEZE | 解冻资金 | 青色 |
| REQUEST_RETURN | 请求回退 | 橙色 |
| QUERY_RETURN | 查询回退 | 灰色 |
| ADD_RECEIVER | 添加接收方 | 绿色 |
| DELETE_RECEIVER | 删除接收方 | 红色 |
| APPLY_BILL | 申请账单 | 紫色 |
| DOWNLOAD_BILL | 下载账单 | 紫色 |
| NOTIFICATION | 通知处理 | 黄色 |

---

## JSON 格式化展示

请求负载和响应负载使用 `TextareaField` + `formatValue` 格式化：

```php
yield TextareaField::new('requestPayload', '请求负载')
    ->formatValue(fn (?string $json) => $this->formatJson($json))
    ->hideOnIndex()
;

private function formatJson(?string $json): string
{
    if (null === $json || '' === $json) {
        return '无';
    }
    $decoded = json_decode($json, true);
    if (JSON_ERROR_NONE !== json_last_error()) {
        return $json;
    }
    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
```

---

## 测试用例

### 单元测试

```php
class ProfitShareOperationLogCrudControllerTest extends AdminWebTestCase
{
    public function testIndexPageLoads(): void
    {
        // Given: 数据库中存在操作日志
        // When: 访问列表页
        // Then: 返回 200，页面包含日志数据
    }

    public function testFilterByType(): void
    {
        // Given: 存在不同类型的日志
        // When: 筛选 type=REQUEST_ORDER
        // Then: 仅返回请求分账类型的日志
    }

    public function testFilterBySuccess(): void
    {
        // Given: 存在成功和失败的日志
        // When: 筛选 success=false
        // Then: 仅返回失败的日志
    }

    public function testDetailShowsFormattedJson(): void
    {
        // Given: 存在包含 JSON 负载的日志
        // When: 访问详情页
        // Then: JSON 以格式化形式展示
    }

    public function testNewActionDisabled(): void
    {
        // Given: 用户访问新建页面
        // When: 尝试访问 /profit-share/log/new
        // Then: 返回 403 或重定向到列表页
    }

    public function testEditActionDisabled(): void
    {
        // Given: 用户访问编辑页面
        // When: 尝试访问 /profit-share/log/{id}/edit
        // Then: 返回 403 或重定向到详情页
    }
}
```

---

## 依赖服务

本控制器仅提供查看功能，不调用业务 Service。日志由各业务 Service 在调用微信 API 时自动记录。
