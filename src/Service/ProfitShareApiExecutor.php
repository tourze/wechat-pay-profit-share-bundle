<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\WechatPayProfitShareBundle\Service\Helper\WechatPayProfitShareHelperTrait;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

/**
 * 微信分账API执行器
 * 负责处理微信分账相关的HTTP请求、响应处理和日志记录
 */
#[WithMonologChannel(channel: 'wechat_pay_profit_share')]
class ProfitShareApiExecutor
{
    use WechatPayProfitShareHelperTrait;

    public function __construct(
        private readonly WechatPayBuilder $wechatPayBuilder,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 执行分账请求
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     * @throws \Throwable
     */
    public function executeRequest(Merchant $merchant, string $segment, array $payload): array
    {
        $builder = $this->wechatPayBuilder->genBuilder($merchant);
        $startTime = microtime(true);

        try {
            $this->logger->info('发起微信分账请求', [
                'segment' => $segment,
                'payload' => $payload,
            ]);

            $response = $builder->chain($segment)->post([
                'json' => $payload,
            ]);
            $body = $response->getBody()->getContents();
            /** @var array<string, mixed> $responseData */
            $responseData = Json::decode($body);

            $this->logger->info('微信分账请求成功', [
                'segment' => $segment,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'response' => $responseData,
            ]);

            return $responseData;
        } catch (\Throwable $exception) {
            $this->logger->error('微信分账请求失败', [
                'segment' => $segment,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * 执行查询请求
     *
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     * @throws \Throwable
     */
    public function executeQuery(Merchant $merchant, string $segment, array $query): array
    {
        $builder = $this->wechatPayBuilder->genBuilder($merchant);
        $startTime = microtime(true);

        try {
            $this->logger->info('查询微信分账结果', [
                'segment' => $segment,
                'query' => $query,
            ]);

            $response = $builder->chain($segment)->get();
            $body = $response->getBody()->getContents();
            /** @var array<string, mixed> $responseData */
            $responseData = Json::decode($body);

            $this->logger->info('微信分账查询成功', [
                'segment' => $segment,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'response' => $responseData,
            ]);

            return $responseData;
        } catch (\Throwable $exception) {
            $this->logger->error('微信分账查询失败', [
                'segment' => $segment,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
