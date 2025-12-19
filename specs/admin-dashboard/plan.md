# 实施方案：微信支付分账后台管理界面

**Feature**: `admin-dashboard` | **Scope**: `packages/wechat-pay-profit-share-bundle` | **日期**: 2025-11-28 | **Spec**: [spec.md](./spec.md)
**输入**: `packages/wechat-pay-profit-share-bundle/specs/admin-dashboard/spec.md`

> 说明：本模板由 `/speckit.plan` 填充。

## 概述

为微信支付分账 Bundle 创建完整的后台管理界面，支持：
- **分账订单管理**：创建、查看、搜索、筛选分账订单
- **解冻剩余资金**：在订单详情页发起解冻操作
- **分账回退**：发起回退请求并查看回退单
- **账单申请**：申请下载分账账单
- **操作日志**：查看所有分账相关 API 操作记录

基于 EasyAdmin 框架实现，复用现有 Service 层调用微信支付 API。

## 技术背景

**语言/版本**：PHP 8.2+
**主要依赖**：
- Symfony 7.x
- EasyAdmin Bundle (easycorp/easyadmin-bundle)
- Doctrine ORM
- tourze/wechat-pay-profit-share-bundle（现有服务层）

**存储**：MySQL/PostgreSQL（使用现有 Doctrine 实体）
**测试**：PHPUnit（镜像 src/ 目录结构）
**目标平台**：Web 后台（内部运营）
**项目类型**：Symfony Bundle 扩展
**性能目标**：列表页 1000 条数据加载 < 3 秒，95% 操作响应 < 2 秒
**约束**：API 调用失败不保存记录，仅基础校验（金额 > 0）
**规模/场景**：内部运营人员使用，预估日均访问 < 1000 次

## 宪章检查

> 阶段门：Phase 0 前必过，Phase 1 后复核。依据 `.specify/memory/constitution.md`。

- [x] **Monorepo 分层架构**：功能归属 `packages/wechat-pay-profit-share-bundle`，依赖边界清晰（EasyAdmin、现有 Service 层）
- [x] **Spec 驱动**：已具备完整的 spec.md（33 项功能需求、9 个用户故事、5 项澄清记录）
- [x] **测试优先**：TDD 策略明确，测试目录镜像 src/Controller/Admin/
- [x] **质量门禁**：PHPStan level 8、PHP-CS-Fixer、PHPUnit 测试覆盖
- [x] **可追溯性**：设计决策记录于 spec.md 澄清记录章节

无宪章违例。

## 项目结构

### 文档（本 Feature）

```text
packages/wechat-pay-profit-share-bundle/specs/admin-dashboard/
├── spec.md              # 功能规格说明书（已完成）
├── plan.md              # 本文件（/speckit.plan 输出）
├── research.md          # Phase 0（/speckit.plan 输出）
├── data-model.md        # Phase 1（/speckit.plan 输出）
├── quickstart.md        # Phase 1（/speckit.plan 输出）
├── contracts/           # Phase 1（/speckit.plan 输出）
│   ├── profit-share-order-crud.md
│   ├── profit-share-return-crud.md
│   ├── profit-share-bill-crud.md
│   └── profit-share-operation-log-crud.md
└── tasks.md             # Phase 2（/speckit.tasks 输出）
```

### 代码结构（Scope 根下）

```text
packages/wechat-pay-profit-share-bundle/
├── src/
│   ├── Entity/                    # 已存在
│   │   ├── ProfitShareOrder.php
│   │   ├── ProfitShareReceiver.php
│   │   ├── ProfitShareReturnOrder.php
│   │   ├── ProfitShareBillTask.php
│   │   └── ProfitShareOperationLog.php
│   │
│   ├── Service/                   # 已存在
│   │   ├── ProfitShareService.php
│   │   ├── ProfitShareReturnService.php
│   │   ├── ProfitShareReceiverService.php
│   │   ├── ProfitShareBillService.php
│   │   └── ...
│   │
│   ├── Controller/Admin/          # 新增（本 Feature）
│   │   ├── ProfitShareOrderCrudController.php      # FR-001~009
│   │   ├── ProfitShareReceiverCrudController.php   # FR-013~014
│   │   ├── ProfitShareReturnOrderCrudController.php # FR-015~021
│   │   ├── ProfitShareBillTaskCrudController.php   # FR-022~026
│   │   └── ProfitShareOperationLogCrudController.php # FR-027~029
│   │
│   └── Resources/config/
│       └── services.yaml          # 可能需要新增服务配置
│
└── tests/
    └── Controller/Admin/          # 新增（镜像 src/）
        ├── ProfitShareOrderCrudControllerTest.php
        ├── ProfitShareReceiverCrudControllerTest.php
        ├── ProfitShareReturnOrderCrudControllerTest.php
        ├── ProfitShareBillTaskCrudControllerTest.php
        └── ProfitShareOperationLogCrudControllerTest.php
```

**结构决策**：采用 Symfony Bundle 标准结构，Controller 放置于 `src/Controller/Admin/`，使用 EasyAdmin 的 `#[AdminCrud]` 属性配置路由。

## 复杂度备案

无宪章违例，不需要填写。
