# 微信支付分账定时任务文档

## 概述

`wechat-pay-profit-share-bundle` 提供了4个核心定时任务，用于自动化处理微信支付分账相关的业务流程，确保系统的健壮性和可靠性。

## 定时任务列表

### 1. 分账状态同步命令

**命令**: `php bin/console wechat:profit-share:sync`

**功能**: 同步处理中状态的分账订单状态，确保本地数据与微信支付端保持一致。

**执行频率**: 每5分钟

**主要特性**:
- 查询所有 `PROCESSING` 状态的订单
- 检查订单是否超时（默认24小时）
- 调用微信支付查询接口同步状态
- 自动更新本地订单状态
- 超时订单告警

**使用示例**:
```bash
# 执行状态同步
php bin/console wechat:profit-share:sync

# 模拟执行，不实际更新数据
php bin/console wechat:profit-share:sync --dry-run

# 设置超时时间为48小时
php bin/console wechat:profit-share:sync --timeout-hours=48
```

### 2. 账单自动下载命令

**命令**: `php bin/console wechat:profit-share:download-bill`

**功能**: 自动下载准备就绪的分账账单文件。

**执行频率**: 每小时

**主要特性**:
- 查询所有 `READY` 状态的账单任务
- 检查账单是否过期（默认7天）
- 自动下载账单文件到指定目录
- 验证文件完整性（哈希校验）
- 按日期和商户组织文件结构

**使用示例**:
```bash
# 下载所有就绪的账单
php bin/console wechat:profit-share:download-bill

# 模拟执行，不实际下载
php bin/console wechat:profit-share:download-bill --dry-run

# 指定下载路径
php bin/console wechat:profit-share:download-bill --download-path=/path/to/bills

# 指定商户
php bin/console wechat:profit-share:download-bill --merchant-id=123
```

### 3. 分账重试机制命令

**命令**: `php bin/console wechat:profit-share:retry`

**功能**: 重试失败的分账接收方，提高分账成功率。

**执行频率**: 每30分钟

**主要特性**:
- 查询失败或长时间处理的接收方
- 支持最大重试次数限制（默认3次）
- 智能重试间隔控制（默认30分钟）
- 自动标记最终失败的接收方
- 详细的重试日志记录

**使用示例**:
```bash
# 重试所有失败的接收方
php bin/console wechat:profit-share:retry

# 模拟执行，不实际重试
php bin/console wechat:profit-share:retry --dry-run

# 设置最大重试次数为5次
php bin/console wechat:profit-share:retry --max-retry=5

# 设置重试间隔为60分钟
php bin/console wechat:profit-share:retry --retry-interval=60
```

### 4. 资金解冻监控命令

**命令**: `php bin/console wechat:profit-share:unfreeze`

**功能**: 监控并执行已完成分账订单的资金解冻操作。

**执行频率**: 每2小时

**主要特性**:
- 查询已完成但未解冻的订单
- 自动执行资金解冻操作
- 可配置解冻时间阈值（默认48小时）
- 支持强制解冻模式
- 防止资金长期冻结

**使用示例**:
```bash
# 执行资金解冻监控
php bin/console wechat:profit-share:unfreeze

# 模拟执行，不实际解冻
php bin/console wechat:profit-share:unfreeze --dry-run

# 设置完成后24小时执行解冻
php bin/console wechat:profit-share:unfreeze --unfreeze-hours=24

# 强制解冻所有符合条件的订单
php bin/console wechat:profit-share:unfreeze --force-unfreeze
```

## Cron 配置建议

推荐使用以下 cron 配置来定时执行这些任务：

```bash
# 分账状态同步 - 每5分钟
*/5 * * * * php /path/to/your/project/bin/console wechat:profit-share:sync >> /var/log/profit-share-sync.log 2>&1

# 账单自动下载 - 每小时
0 * * * * php /path/to/your/project/bin/console wechat:profit-share:download-bill >> /var/log/profit-share-bill.log 2>&1

# 分账重试机制 - 每30分钟
*/30 * * * * php /path/to/your/project/bin/console wechat:profit-share:retry >> /var/log/profit-share-retry.log 2>&1

# 资金解冻监控 - 每2小时
0 */2 * * * php /path/to/your/project/bin/console wechat:profit-share:unfreeze >> /var/log/profit-share-unfreeze.log 2>&1
```

## 监控和告警

### 日志监控

所有定时任务都会输出详细的日志，建议配置日志监控：

- **分账同步**: 监控超时订单和状态更新异常
- **账单下载**: 监控下载失败和过期任务
- **分账重试**: 监控重试失败和达到最大重试次数的接收方
- **资金解冻**: 监控解冻失败和长时间未解冻的订单

### 告警建议

建议配置以下告警规则：

1. **分账订单超时告警**: 订单处理时间超过阈值
2. **账单下载失败告警**: 连续3次下载失败
3. **重试次数超限告警**: 接收方达到最大重试次数
4. **资金解冻异常告警**: 解冻操作失败

## 数据库变更

新增了以下字段来支持定时任务功能：

### ProfitShareReceiver 表新增字段：

```sql
ALTER TABLE wechat_pay_profit_share_receiver 
ADD COLUMN retry_count INT DEFAULT 0 COMMENT '重试次数',
ADD COLUMN next_retry_at DATETIME DEFAULT NULL COMMENT '下次重试时间',
ADD COLUMN finally_failed BOOLEAN DEFAULT FALSE COMMENT '是否最终失败';
```

### ProfitShareBillTask 状态枚举：

需要新增 `EXPIRED` 状态到 `ProfitShareBillStatus` 枚举。

## 最佳实践

1. **测试环境验证**: 在生产环境使用前，请在测试环境充分验证
2. **逐步部署**: 建议先部署状态同步任务，再逐步添加其他任务
3. **监控告警**: 配置完善的监控和告警机制
4. **日志分析**: 定期分析日志，优化任务执行效率
5. **参数调优**: 根据实际业务情况调整任务参数

## 故障处理

### 常见问题

1. **任务执行失败**: 检查网络连接和微信支付API权限
2. **数据库连接问题**: 确保数据库连接正常
3. **内存不足**: 适当调整 PHP 内存限制
4. **权限问题**: 确保文件写入权限正确

### 故障恢复

1. **手动重试**: 使用 `--dry-run` 参数先模拟执行
2. **数据修复**: 根据日志信息修复异常数据
3. **参数调整**: 根据实际情况调整任务参数
4. **紧急处理**: 必要时停止定时任务，手动处理异常

## 性能优化

1. **批量处理**: 对于大量数据，考虑分批处理
2. **并发控制**: 避免多个实例同时执行相同任务
3. **缓存优化**: 合理使用缓存减少数据库查询
4. **索引优化**: 确保相关字段有适当的数据库索引

---

如有问题，请查看相关日志文件或联系技术支持团队。