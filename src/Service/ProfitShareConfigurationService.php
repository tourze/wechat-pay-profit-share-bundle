<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOperationLog;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOperationType;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOperationLogRepository;
use Tourze\WechatPayProfitShareBundle\Service\Helper\WechatPayProfitShareHelperTrait;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

#[WithMonologChannel(channel: 'wechat_pay_profit_share')]
final class ProfitShareConfigurationService
{
    use WechatPayProfitShareHelperTrait;

    public function __construct(
        private readonly ProfitShareOperationLogRepository $operationLogRepository,
        private readonly WechatPayBuilder $wechatPayBuilder,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function queryRemainingAmount(Merchant $merchant, string $transactionId): array
    {
        $builder = $this->wechatPayBuilder->genBuilder($merchant);
        $segment = sprintf('v3/profitsharing/transactions/%s/amounts', rawurlencode($transactionId));
        $startTime = microtime(true);

        try {
            $this->logger->info('查询分账剩余待分金额', [
                'transaction_id' => $transactionId,
            ]);

            $response = $builder->chain($segment)->get();
            $body = $response->getBody()->getContents();
            /** @var array<string, mixed> $data */
            $data = Json::decode($body);

            $this->logger->info('查询分账剩余待分金额成功', [
                'transaction_id' => $transactionId,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            $transactionId = $data['transaction_id'] ?? null;
            $this->logOperation(
                $merchant,
                \is_string($transactionId) ? $transactionId : null,
                ProfitShareOperationType::QUERY_REMAINING_AMOUNT,
                true,
                null,
                null,
                ['transaction_id' => $transactionId],
                $data,
            );

            return $data;
        } catch (\Throwable $exception) {
            $this->logger->error('查询分账剩余待分金额失败', [
                'transaction_id' => $transactionId,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'exception' => $exception->getMessage(),
            ]);

            $this->logOperation(
                $merchant,
                null,
                ProfitShareOperationType::QUERY_REMAINING_AMOUNT,
                false,
                $this->extractErrorCode($exception),
                $exception->getMessage(),
                ['transaction_id' => $transactionId],
                ['exception' => $exception->getMessage()],
            );

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $request
     * @param array<string, mixed>|string|null $response
     */
    private function logOperation(
        Merchant $merchant,
        ?string $subMchId,
        ProfitShareOperationType $type,
        bool $success,
        ?string $errorCode,
        ?string $errorMessage,
        array $request,
        $response,
    ): void {
        $log = new ProfitShareOperationLog();
        $log->setMerchant($merchant);
        $log->setSubMchId($subMchId);
        $log->setType($type);
        $log->setSuccess($success);
        $log->setErrorCode($errorCode);
        $log->setErrorMessage($errorMessage);
        $log->setRequestPayload($this->encodePayload($request));
        $log->setResponsePayload($this->encodePayload($response));

        $this->operationLogRepository->save($log);
    }

    /**
     * @param array<string, mixed>|string|null $payload
     */
    private function encodePayload($payload): ?string
    {
        if (null === $payload || '' === $payload) {
            return null;
        }

        if (\is_string($payload)) {
            return $payload;
        }

        return Json::encode($payload);
    }

    private function extractErrorCode(\Throwable $exception): ?string
    {
        $code = $exception->getCode();

        return 0 === $code ? null : (string) $code;
    }

    /**
     * @return array<string, mixed>
     */
    public function queryMaxRatio(Merchant $merchant, string $subMchId): array
    {
        $builder = $this->wechatPayBuilder->genBuilder($merchant);
        $segment = sprintf('v3/profitsharing/merchant-configs/%s', rawurlencode($subMchId));
        $startTime = microtime(true);

        try {
            $this->logger->info('查询特约商户最大分账比例', [
                'sub_mchid' => $subMchId,
            ]);

            $response = $builder->chain($segment)->get();
            $body = $response->getBody()->getContents();
            /** @var array<string, mixed> $data */
            $data = Json::decode($body);

            $this->logger->info('查询特约商户最大分账比例成功', [
                'sub_mchid' => $subMchId,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            $this->logOperation(
                $merchant,
                $subMchId,
                ProfitShareOperationType::QUERY_MAX_RATIO,
                true,
                null,
                null,
                ['sub_mchid' => $subMchId],
                $data,
            );

            return $data;
        } catch (\Throwable $exception) {
            $this->logger->error('查询特约商户最大分账比例失败', [
                'sub_mchid' => $subMchId,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'exception' => $exception->getMessage(),
            ]);

            $this->logOperation(
                $merchant,
                $subMchId,
                ProfitShareOperationType::QUERY_MAX_RATIO,
                false,
                $this->extractErrorCode($exception),
                $exception->getMessage(),
                ['sub_mchid' => $subMchId],
                ['exception' => $exception->getMessage()],
            );

            throw $exception;
        }
    }
}
