<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Service;

use Psr\Log\LoggerInterface;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOperationLog;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOperationType;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOperationLogRepository;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReceiverAddRequest;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReceiverDeleteRequest;
use Tourze\WechatPayProfitShareBundle\Service\Helper\WechatPayProfitShareHelperTrait;
use WeChatPay\Crypto\Rsa;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

class ProfitShareReceiverService
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
    public function addReceiver(Merchant $merchant, ProfitShareReceiverAddRequest $request): array
    {
        $payload = $request->toPayload();

        if (isset($payload['name']) && '' !== $payload['name']) {
            $publicKey = $this->wechatPayBuilder->getPlatformPublicKey($merchant);
            $payload['name'] = Rsa::encrypt($payload['name'], $publicKey);
        }

        $builder = $this->wechatPayBuilder->genBuilder($merchant);
        $segment = 'v3/profitsharing/receivers/add';
        $headers = [
            'headers' => [
                'Wechatpay-Serial' => $this->wechatPayBuilder->getPlatformCertificateSerial($merchant),
            ],
            'json' => $payload,
        ];

        $startTime = microtime(true);

        try {
            $this->logger->info('添加微信分账接收方', [
                'sub_mchid' => $payload['sub_mchid'],
                'account' => $payload['account'],
                'type' => $payload['type'],
            ]);

            $response = $builder->chain($segment)->post($headers);
            $body = $response->getBody()->getContents();
            /** @var array<string, mixed> $responseData */
            $responseData = Json::decode($body);

            $this->logger->info('添加分账接收方成功', [
                'account' => $payload['account'],
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            $this->logOperation(
                $merchant,
                $payload['sub_mchid'],
                ProfitShareOperationType::ADD_RECEIVER,
                true,
                null,
                null,
                $payload,
                $responseData,
            );

            return $responseData;
        } catch (\Throwable $exception) {
            $this->logger->error('添加分账接收方失败', [
                'account' => $payload['account'],
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'exception' => $exception->getMessage(),
            ]);

            $this->logOperation(
                $merchant,
                $payload['sub_mchid'],
                ProfitShareOperationType::ADD_RECEIVER,
                false,
                $this->extractErrorCode($exception),
                $exception->getMessage(),
                $payload,
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
    public function deleteReceiver(Merchant $merchant, ProfitShareReceiverDeleteRequest $request): array
    {
        $payload = $request->toPayload();

        $builder = $this->wechatPayBuilder->genBuilder($merchant);
        $segment = 'v3/profitsharing/receivers/delete';
        $headers = [
            'json' => $payload,
        ];

        $startTime = microtime(true);

        try {
            $this->logger->info('删除微信分账接收方', [
                'sub_mchid' => $payload['sub_mchid'],
                'account' => $payload['account'],
                'type' => $payload['type'],
            ]);

            $response = $builder->chain($segment)->post($headers);
            $body = $response->getBody()->getContents();
            /** @var array<string, mixed> $responseData */
            $responseData = Json::decode($body);

            $this->logger->info('删除分账接收方成功', [
                'account' => $payload['account'],
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            $this->logOperation(
                $merchant,
                $payload['sub_mchid'],
                ProfitShareOperationType::DELETE_RECEIVER,
                true,
                null,
                null,
                $payload,
                $responseData,
            );

            return $responseData;
        } catch (\Throwable $exception) {
            $this->logger->error('删除分账接收方失败', [
                'account' => $payload['account'],
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'exception' => $exception->getMessage(),
            ]);

            $this->logOperation(
                $merchant,
                $payload['sub_mchid'],
                ProfitShareOperationType::DELETE_RECEIVER,
                false,
                $this->extractErrorCode($exception),
                $exception->getMessage(),
                $payload,
                ['exception' => $exception->getMessage()],
            );

            throw $exception;
        }
    }
}
