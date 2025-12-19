<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Controller\Admin\Traits;

/**
 * JSON 格式化 Trait
 *
 * 提供 JSON 负载的格式化展示方法
 */
trait JsonFormatterTrait
{
    /**
     * 格式化 JSON 字符串以便展示
     *
     * @param string|null $json 原始 JSON 字符串
     * @return string 格式化后的 JSON 字符串
     */
    protected function formatJson(?string $json): string
    {
        if (null === $json || '' === $json) {
            return '无';
        }

        $decoded = json_decode($json, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            // 无法解析为 JSON，返回原始字符串
            return $json;
        }

        $formatted = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return false !== $formatted ? $formatted : $json;
    }

    /**
     * 格式化并脱敏 JSON 字符串
     *
     * @param string|null $json 原始 JSON 字符串
     * @param array<string> $sensitiveFields 需要脱敏的字段名列表
     * @return string 格式化并脱敏后的 JSON 字符串
     */
    protected function formatAndMaskJson(?string $json, array $sensitiveFields = []): string
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
            'certificate',
            'serial_no',
            'private_key',
            'mch_private_key',
            'apiclient_key',
        ];

        $allSensitiveFields = array_unique(array_merge($defaultSensitiveFields, $sensitiveFields));

        // 递归脱敏
        array_walk_recursive($decoded, function (&$value, $key) use ($allSensitiveFields): void {
            if (is_string($value) && in_array($key, $allSensitiveFields, true)) {
                $length = strlen($value);
                if ($length > 10) {
                    $value = substr($value, 0, 4) . '...[已隐藏]...' . substr($value, -4);
                } else {
                    $value = '[已隐藏]';
                }
            }
        });

        $result = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return false !== $result ? $result : $json;
    }

    /**
     * 截断 JSON 字符串用于列表展示
     *
     * @param string|null $json 原始 JSON 字符串
     * @param int $maxLength 最大长度
     * @return string 截断后的字符串
     */
    protected function truncateJson(?string $json, int $maxLength = 100): string
    {
        if (null === $json || '' === $json) {
            return '无';
        }

        if (mb_strlen($json) <= $maxLength) {
            return $json;
        }

        return mb_substr($json, 0, $maxLength) . '...';
    }
}
