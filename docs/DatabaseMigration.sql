-- 微信支付分账定时任务功能数据库迁移
-- 版本: v1.1.0
-- 说明: 添加定时任务相关字段支持

-- 1. 为 wechat_pay_profit_share_receiver 表添加重试相关字段
ALTER TABLE wechat_pay_profit_share_receiver 
ADD COLUMN retry_count INT NOT NULL DEFAULT 0 COMMENT '重试次数',
ADD COLUMN next_retry_at DATETIME DEFAULT NULL COMMENT '下次重试时间',
ADD COLUMN finally_failed BOOLEAN NOT NULL DEFAULT FALSE COMMENT '是否最终失败';

-- 2. 为 wechat_pay_profit_share_receiver 表添加索引
CREATE INDEX idx_profit_share_receiver_retry ON wechat_pay_profit_share_receiver (finally_failed, result, retry_count);
CREATE INDEX idx_profit_share_receiver_next_retry ON wechat_pay_profit_share_receiver (next_retry_at, finally_failed);

-- 3. 为 wechat_pay_profit_share_order 表添加索引
CREATE INDEX idx_profit_share_order_state_created ON wechat_pay_profit_share_order (state, created_at);
CREATE INDEX idx_profit_share_order_finished_unfreeze ON wechat_pay_profit_share_order (state, unfreeze_unsplit, wechat_finished_at);

-- 4. 为 wechat_pay_profit_share_bill_task 表添加索引
CREATE INDEX idx_profit_share_bill_task_status_created ON wechat_pay_profit_share_bill_task (status, created_at);

-- 5. 更新现有数据（可选）
-- 将现有的 FAILED 状态接收方设置为需要重试状态
UPDATE wechat_pay_profit_share_receiver 
SET retry_count = 1, 
    next_retry_at = DATE_ADD(NOW(), INTERVAL 30 MINUTE),
    finally_failed = FALSE
WHERE result = 'FAILED' AND finally_failed = FALSE;

-- 6. 数据清理（可选，根据实际情况调整）
-- 清理超过30天的重试失败记录
-- DELETE FROM wechat_pay_profit_share_receiver 
-- WHERE finally_failed = TRUE 
-- AND updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- 说明：
-- 1. 请在业务低峰期执行此迁移
-- 2. 执行前请备份相关数据表
-- 3. 根据实际数据库调整字段类型和索引
-- 4. 迁移完成后请验证定时任务功能正常