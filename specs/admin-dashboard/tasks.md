# 任务清单：微信支付分账后台管理界面

**Feature**: `admin-dashboard`
**Scope**: `packages/wechat-pay-profit-share-bundle`
**输入**: `specs/admin-dashboard/` 下的设计文档
**前置**: plan.md、spec.md、research.md、data-model.md、contracts/*.md
**日期**: 2025-11-28

## 用户故事映射

| 用户故事 | 优先级 | 对应契约 | 功能需求 |
|----------|--------|----------|----------|
| US1 - 创建分账订单 | P0 | profit-share-order-crud.md | FR-001~005 |
| US2 - 分账订单查看与管理 | P1 | profit-share-order-crud.md | FR-006~009 |
| US3 - 分账接收方明细查看 | P1 | profit-share-order-crud.md | FR-013~014 |
| US4 - 解冻剩余资金 | P1 | profit-share-order-crud.md | FR-010~012 |
| US5 - 发起分账回退 | P1 | profit-share-order-crud.md | FR-015~019 |
| US6 - 分账回退单查看 | P2 | profit-share-return-crud.md | FR-020~021 |
| US7 - 申请分账账单 | P2 | profit-share-bill-crud.md | FR-022~024 |
| US8 - 分账账单任务查看 | P2 | profit-share-bill-crud.md | FR-025~026 |
| US9 - 操作日志查询 | P3 | profit-share-operation-log-crud.md | FR-027~029 |

## 格式说明

- **[P]**：可并行执行（不同文件、无未完成依赖）
- **[USx]**：所属用户故事
- 描述中包含具体文件路径（相对 Scope 根）

---

## Phase 1: 初始化（通用基础）

**目的**: 创建 Controller 目录结构和基础配置

- [x] T001 创建 Admin 控制器目录 src/Controller/Admin/
- [x] T002 创建测试目录 tests/Controller/Admin/
- [x] T003 [P] 创建分账接收方 FormType src/Form/ProfitShareReceiverType.php

---

## Phase 2: 基础能力（阻塞项）

**目的**: 所有用户故事开始前必须完成的公共组件

**⚠️ 关键**: 未完成前禁止进入任意用户故事

- [x] T004 创建 AdminWebTestCase 测试基类 tests/Controller/Admin/AdminWebTestCase.php
- [x] T005 [P] 创建 CrudController 基类/Trait（公共脱敏方法） src/Controller/Admin/Traits/SensitiveDataMaskingTrait.php
  - 实现 maskName()、maskSensitiveJson() 等脱敏方法（FR-032）
- [x] T006 [P] 创建 JSON 格式化 Trait src/Controller/Admin/Traits/JsonFormatterTrait.php
  - 实现 formatJson() 格式化 JSON 负载
- [x] T006a [P] 创建 AbstractProfitShareCrudController 基类 src/Controller/Admin/AbstractProfitShareCrudController.php
  - 配置默认分页（每页 20 条，最大 100 条）（FR-030）
  - 配置默认按 createTime DESC 排序（FR-031）
  - 引入 SensitiveDataMaskingTrait 和 JsonFormatterTrait

**检查点**: 基础组件就绪，可并行开展故事

---

## Phase 3: 用户故事 9 - 操作日志查询（优先级：P3）

**目标**: 提供只读的操作日志查看界面，支持筛选和详情查看

**独立验证**: 登录后台，进入操作日志列表页，可按类型/状态筛选，查看日志详情

**选择理由**: 最简单的只读 CRUD，无写操作，用于验证 EasyAdmin 基础配置

### 实现

- [x] T007 [US9] 创建 ProfitShareOperationLogCrudController src/Controller/Admin/ProfitShareOperationLogCrudController.php
  - 实现 configureFields()：id, type, subMchId, success, errorCode, errorMessage, createTime
  - 实现 configureFilters()：type(ChoiceFilter), success(BooleanFilter), subMchId(TextFilter), createTime(DateTimeFilter)
  - 实现 configureActions()：禁用 NEW, EDIT, DELETE
  - 使用 JsonFormatterTrait 格式化 requestPayload, responsePayload
- [x] T008 [US9] 创建测试 tests/Controller/Admin/ProfitShareOperationLogCrudControllerTest.php
  - testIndexPageLoads
  - testFilterByType
  - testFilterBySuccess
  - testDetailShowsFormattedJson
  - testNewActionDisabled

**检查点**: 操作日志模块可独立运行与测试

---

## Phase 4: 用户故事 8 - 分账账单任务查看（优先级：P2）

**目标**: 提供账单任务的只读列表和详情查看

**独立验证**: 登录后台，进入账单任务列表页，可按状态/日期筛选，查看任务详情

### 实现

- [x] T009 [P] [US8] 创建 ProfitShareBillTaskCrudController 基础结构 src/Controller/Admin/ProfitShareBillTaskCrudController.php
  - 实现 configureFields()：id, billDate, subMchId, status, downloadUrl, downloadedAt, createTime
  - 实现 configureFilters()：status(ChoiceFilter), billDate(DateFilter), subMchId(TextFilter)
  - 实现 configureActions()：禁用 EDIT, DELETE
  - 状态标签颜色配置
- [x] T010 [US8] 创建测试 tests/Controller/Admin/ProfitShareBillTaskCrudControllerTest.php
  - testIndexPageLoads
  - testFilterByStatus
  - testFilterByDateRange

**检查点**: 账单任务查看可独立运行

---

## Phase 5: 用户故事 7 - 申请分账账单（优先级：P2）

**目标**: 在账单任务管理中添加"申请账单"操作

**独立验证**: 点击"申请账单"，填写日期后提交，系统调用微信 API

**依赖**: T009（ProfitShareBillTaskCrudController 基础结构）

### 实现

- [x] T011 [US7] 扩展 ProfitShareBillTaskCrudController 添加申请账单 Action src/Controller/Admin/ProfitShareBillTaskCrudController.php
  - 添加 persistEntity() 方法调用 ProfitShareBillService::applyBill()
  - 表单包含 merchant, billDate, subMchId
  - 错误处理：缺失商户/日期、API 错误
- [x] T012 [US7] 测试更新 tests/Controller/Admin/ProfitShareBillTaskCrudControllerTest.php
  - 更新 mock 依赖注入

**检查点**: 账单申请功能可独立运行

---

## Phase 6: 用户故事 6 - 分账回退单查看（优先级：P2）

**目标**: 提供分账回退单的只读列表和详情查看

**独立验证**: 登录后台，进入回退单列表页，可搜索/筛选，查看详情

### 实现

- [x] T013 [P] [US6] 创建 ProfitShareReturnOrderCrudController src/Controller/Admin/ProfitShareReturnOrderCrudController.php
  - 实现 configureFields()：id, outReturnNo, returnNo, outOrderNo, subMchId, amount, result, createTime
  - 实现 configureFilters()：result(ChoiceFilter), subMchId(TextFilter), createTime(DateTimeFilter)
  - 实现 configureActions()：禁用 NEW, EDIT, DELETE
  - 金额格式化（分 → 元）
  - 使用 SensitiveDataMaskingTrait 脱敏负载
- [x] T014 [US6] 创建测试 tests/Controller/Admin/ProfitShareReturnOrderCrudControllerTest.php
  - testIndexPageLoads
  - testSearchByOutReturnNo
  - testFilterByResult
  - testDetailPageShowsFailReason
  - testNewActionDisabled

**检查点**: 回退单查看可独立运行

---

## Phase 7: 用户故事 2 - 分账订单查看与管理（优先级：P1）

**目标**: 提供分账订单的列表、搜索、筛选和详情查看

**独立验证**: 登录后台，进入分账订单列表页，可搜索/筛选，查看订单详情

### 实现

- [x] T015 [P] [US2] 创建 ProfitShareOrderCrudController 基础结构 src/Controller/Admin/ProfitShareOrderCrudController.php
  - 继承 AbstractProfitShareCrudController
  - 实现 getEntityFqcn() 返回 ProfitShareOrder
  - 实现 configureCrud()：设置标签、默认排序、搜索字段
  - 实现 configureFields() Index/Detail 字段：id, outOrderNo, orderId, subMchId, transactionId, state, unfreezeUnsplit, createTime, updateTime（FR-006）
  - 实现 configureFilters()：state(ChoiceFilter：PROCESSING/FINISHED/CLOSED), subMchId(TextFilter)
  - 状态标签颜色配置（处理中-灰色、已完成-绿色、已关闭-红色）
- [x] T016 [US2] 创建测试基础 tests/Controller/Admin/ProfitShareOrderCrudControllerTest.php
  - testIndexPageLoads
  - testSearchByOutOrderNo
  - testFilterByState

**检查点**: 订单查看可独立运行

---

## Phase 8: 用户故事 3 - 分账接收方明细查看（优先级：P1）

**目标**: 在订单详情中展示关联的接收方列表

**独立验证**: 查看订单详情页，可看到该订单下所有接收方及其分账结果

**依赖**: T015（ProfitShareOrderCrudController 基础结构）

### 实现

- [x] T017 [US3] 扩展 ProfitShareOrderCrudController 详情页展示接收方 src/Controller/Admin/ProfitShareOrderCrudController.php
  - 在 configureFields() Detail 添加 AssociationField receivers
  - 配置接收方字段：type, account, name(脱敏), amount, description, result, failReason
- [x] T018 [P] [US3] 创建 ProfitShareReceiverCrudController（可选独立列表） src/Controller/Admin/ProfitShareReceiverCrudController.php
  - 只读列表，支持按 result 筛选
  - 链接到关联订单
- [x] T019 [US3] 扩展测试 tests/Controller/Admin/ProfitShareOrderCrudControllerTest.php
  - testDetailShowsReceivers
  - testReceiverNameMasked

**检查点**: 接收方明细可在订单详情中查看

---

## Phase 9: 用户故事 4 - 解冻剩余资金（优先级：P1）

**目标**: 在订单详情页提供"解冻剩余资金"操作

**独立验证**: 查看已完成订单详情，点击"解冻剩余资金"，填写描述后提交

**依赖**: T015（ProfitShareOrderCrudController 基础结构）

### 实现

- [x] T020 [US4] 扩展 ProfitShareOrderCrudController 添加解冻 Action src/Controller/Admin/ProfitShareOrderCrudController.php
  - 在 configureActions() 添加 unfreezeAction，仅当 state=PROCESSING 时显示
  - 实现 unfreezeAction() 方法：调用 ProfitShareService::unfreezeRemainingAmount()、错误处理
- [x] T021 [US4] 测试更新 tests/Controller/Admin/ProfitShareOrderCrudControllerTest.php
  - 更新 mock 依赖注入

**检查点**: 解冻操作可独立运行

---

## Phase 10: 用户故事 5 - 发起分账回退（优先级：P1）

**目标**: 在订单详情页提供"发起回退"操作

**独立验证**: 查看已完成订单详情，点击"发起回退"，填写回退信息后提交

**依赖**: T015（ProfitShareOrderCrudController 基础结构）

### 实现

- [x] T022 [US5] 扩展 ProfitShareOrderCrudController 添加回退 Action src/Controller/Admin/ProfitShareOrderCrudController.php
  - 在 configureActions() 添加 returnAction，仅当 state=FINISHED 时显示
  - 实现 returnAction() 方法：调用 ProfitShareReturnService::requestReturn()、错误处理
- [x] T023 [US5] 测试更新 tests/Controller/Admin/ProfitShareOrderCrudControllerTest.php
  - 更新 mock 依赖注入

**检查点**: 回退操作可独立运行

---

## Phase 11: 用户故事 1 - 创建分账订单（优先级：P0）🎯 MVP

**目标**: 提供完整的分账订单创建表单，支持添加多个接收方

**独立验证**: 登录后台，点击"创建分账订单"，填写信息并添加接收方后提交

**依赖**: T003（ProfitShareReceiverType）、T015（ProfitShareOrderCrudController 基础结构）

### 实现

- [x] T024 [US1] ProfitShareReceiverType 已在 Phase 1 创建 src/Form/ProfitShareReceiverType.php
  - 字段：type(ChoiceType), account(TextType), name(TextType,optional), amount(IntegerType), description(TextType)
- [x] T025 [US1] 扩展 ProfitShareOrderCrudController 添加创建功能 src/Controller/Admin/ProfitShareOrderCrudController.php
  - 在 configureFields() NEW 页添加：merchant, subMchId, transactionId, outOrderNo, unfreezeUnsplit, receivers(CollectionField)
  - 实现 persistEntity() 调用 ProfitShareService::requestProfitShare()
  - 错误处理：缺失必填字段、API 错误
- [x] T026 [US1] 测试更新 tests/Controller/Admin/ProfitShareOrderCrudControllerTest.php
  - 更新 mock 依赖注入

**检查点**: 分账订单创建功能可独立运行，MVP 完成

---

## Phase 12: 打磨与跨领域

- [x] T027 [P] 统一所有控制器的状态/类型标签颜色配置
- [x] T028 [P] Flash 消息使用中文（已实现，多语言支持可后续迭代）
- [x] T029 运行完整质量门检查
  - PHPStan: `./vendor/bin/phpstan analyse -c phpstan.neon packages/wechat-pay-profit-share-bundle/src/Controller/Admin/`
  - PHP-CS-Fixer: `./vendor/bin/php-cs-fixer fix packages/wechat-pay-profit-share-bundle/src/Controller/Admin/ --dry-run`
  - PHPUnit: `./vendor/bin/phpunit packages/wechat-pay-profit-share-bundle/tests/Controller/Admin/`
- [x] T030 运行 quickstart.md 校验流程（PHPStan 0 errors, PHPUnit 42 tests passed）

---

## 依赖与执行顺序

```
Phase 1 (初始化)
    │
    ▼
Phase 2 (基础能力) ─────────────────────────────────────────┐
    │                                                        │
    ├──────────────┬──────────────┬──────────────┐          │
    ▼              ▼              ▼              ▼          │
Phase 3        Phase 4        Phase 6        Phase 7        │
(US9:日志)     (US8:账单查看) (US6:回退查看) (US2:订单查看) │
    │              │                             │          │
    │              ▼                             │          │
    │          Phase 5                           │          │
    │          (US7:申请账单)                     │          │
    │                                            │          │
    │              ┌─────────────────────────────┤          │
    │              ▼              ▼              ▼          │
    │          Phase 8        Phase 9        Phase 10       │
    │          (US3:接收方)   (US4:解冻)     (US5:回退)     │
    │                                            │          │
    │                                            ▼          │
    │                                        Phase 11       │
    │                                        (US1:创建) 🎯  │
    │                                            │          │
    └────────────────────────────────────────────┼──────────┘
                                                 ▼
                                            Phase 12
                                            (打磨)
```

### 并行执行机会

| 阶段组 | 可并行任务 |
|--------|-----------|
| Phase 2 | T005, T006, T006a |
| Phase 3-7 | Phase 3, Phase 4, Phase 6, Phase 7 可完全并行（无依赖） |
| Phase 8-10 | Phase 8, Phase 9, Phase 10 可并行（均依赖 Phase 7） |
| Phase 12 | T027, T028 |

### MVP 范围

**建议 MVP**：完成 Phase 1-2 + Phase 7 + Phase 11

- Phase 1: 初始化
- Phase 2: 基础能力
- Phase 7: 分账订单查看（US2）
- Phase 11: 创建分账订单（US1）

MVP 交付后即可进行基本的分账订单创建和查看操作，满足核心业务需求。

---

## 实现策略

1. **渐进式交付**：从最简单的只读模块（US9 操作日志）开始，逐步增加复杂度
2. **测试驱动**：每个功能模块先写测试再实现
3. **复用优先**：利用 Trait 复用脱敏、JSON 格式化等公共逻辑
4. **独立验收**：每个 Phase 完成后可独立验收，无需等待全部完成
