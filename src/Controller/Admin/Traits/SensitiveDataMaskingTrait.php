<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Controller\Admin\Traits;

/**
 * 敏感数据脱敏 Trait
 *
 * 提供姓名、JSON 负载等敏感信息的脱敏方法（FR-032）
 */
trait SensitiveDataMaskingTrait
{
    /**
     * 脱敏姓名（保留首尾字符）
     *
     * 示例：
     * - "张三" -> "张*"
     * - "张三丰" -> "张*丰"
     * - "欧阳修" -> "欧*修"
     */
    protected function maskName(?string $name): string
    {
        if (null === $name || '' === $name) {
            return '未设置';
        }

        $length = mb_strlen($name);

        if ($length <= 1) {
            return $name;
        }

        if (2 === $length) {
            return mb_substr($name, 0, 1) . '*';
        }

        return mb_substr($name, 0, 1) . str_repeat('*', $length - 2) . mb_substr($name, -1);
    }

    /**
     * 脱敏 JSON 负载中的敏感字段
     *
     * @param string|null $json 原始 JSON 字符串
     * @param array<string> $sensitiveFields 需要脱敏的字段名列表
     * @return string 脱敏后的 JSON 字符串
     */
    protected function maskSensitiveJson(?string $json, array $sensitiveFields = []): string
    {
        if (null === $json || '' === $json) {
            return '无';
        }

        $decoded = json_decode($json, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            return $json;
        }

        // 默认敏感字段
        $defaultSensitiveFields = [
            'name',
            'receiver_name',
            'payer_name',
            'idcard_number',
            'id_card_number',
            'phone_number',
            'mobile',
            'certificate',
            'serial_no',
            'private_key',
            'mch_private_key',
        ];

        $allSensitiveFields = array_unique(array_merge($defaultSensitiveFields, $sensitiveFields));

        // 递归脱敏
        $this->maskArrayRecursive($decoded, $allSensitiveFields);

        $result = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return false !== $result ? $result : $json;
    }

    /**
     * 递归脱敏数组中的敏感字段
     *
     * @param array<string, mixed> $data
     * @param array<string> $sensitiveFields
     */
    private function maskArrayRecursive(array &$data, array $sensitiveFields): void
    {
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $this->maskArrayRecursive($value, $sensitiveFields);
            } elseif (is_string($value) && in_array($key, $sensitiveFields, true)) {
                $value = $this->maskStringValue($value);
            }
        }
    }

    /**
     * 脱敏字符串值（保留首尾部分）
     */
    private function maskStringValue(string $value): string
    {
        $length = mb_strlen($value);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        if ($length <= 8) {
            return mb_substr($value, 0, 1) . str_repeat('*', $length - 2) . mb_substr($value, -1);
        }

        // 较长字符串保留前2后2
        return mb_substr($value, 0, 2) . str_repeat('*', min($length - 4, 6)) . mb_substr($value, -2);
    }
}
