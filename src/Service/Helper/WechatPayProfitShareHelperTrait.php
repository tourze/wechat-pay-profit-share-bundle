<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Service\Helper;

use Psr\Log\LoggerInterface;

trait WechatPayProfitShareHelperTrait
{
    protected function parseWechatTime(mixed $value, LoggerInterface $logger): ?\DateTimeImmutable
    {
        if (!\is_string($value) || '' === $value) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable $exception) {
            $logger->warning('解析微信时间失败', [
                'value' => $value,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    protected function minDate(?\DateTimeImmutable $current, \DateTimeImmutable $candidate): \DateTimeImmutable
    {
        if (null === $current) {
            return $candidate;
        }

        return $candidate < $current ? $candidate : $current;
    }

    protected function maxDate(?\DateTimeImmutable $current, \DateTimeImmutable $candidate): \DateTimeImmutable
    {
        if (null === $current) {
            return $candidate;
        }

        return $candidate > $current ? $candidate : $current;
    }
}
