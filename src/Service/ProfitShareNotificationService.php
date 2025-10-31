<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Service;

use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOperationLog;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOperationType;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOperationLogRepository;
use Tourze\WechatPayProfitShareBundle\Service\Helper\WechatPayProfitShareHelperTrait;
use WeChatPay\Crypto\AesGcm;
use WeChatPay\Crypto\Rsa;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

class ProfitShareNotificationService
{
    use WechatPayProfitShareHelperTrait;

    public function __construct(
        private readonly ProfitShareOperationLogRepository $operationLogRepository,
        private readonly WechatPayBuilder $wechatPayBuilder,
    ) {
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<string, mixed>
     */
    public function handleNotification(Merchant $merchant, string $body, array $headers): array
    {
        /** @var array<string, mixed> $payload */
        $payload = Json::decode($body);

        $normalizedHeaders = $this->normalizeHeaders($headers);
        $signature = $normalizedHeaders['WECHATPAY-SIGNATURE'] ?? null;
        $timestamp = $normalizedHeaders['WECHATPAY-TIMESTAMP'] ?? null;
        $nonce = $normalizedHeaders['WECHATPAY-NONCE'] ?? null;

        if (null === $signature || null === $timestamp || null === $nonce) {
            throw new \InvalidArgumentException('回调签名头缺失');
        }

        $message = $timestamp . "\n" . $nonce . "\n" . $body . "\n";
        $publicKey = $this->wechatPayBuilder->getPlatformPublicKey($merchant);

        if (!Rsa::verify($message, $signature, $publicKey)) {
            throw new \RuntimeException('微信回调验签失败');
        }

        $resource = $payload['resource'] ?? null;
        if (!\is_array($resource)) {
            throw new \RuntimeException('回调资源内容缺失');
        }

        $apiV3Key = $merchant->getApiKey();
        if (null === $apiV3Key || '' === $apiV3Key) {
            throw new \RuntimeException('未配置APIv3密钥，无法解密回调');
        }

        $associatedDataRaw = $resource['associated_data'] ?? '';
        $associatedData = \is_string($associatedDataRaw) ? $associatedDataRaw : '';
        $ciphertextRaw = $resource['ciphertext'] ?? '';
        $ciphertext = \is_string($ciphertextRaw) ? $ciphertextRaw : '';
        $nonceRaw = $resource['nonce'] ?? '';
        $nonceValue = \is_string($nonceRaw) ? $nonceRaw : '';

        $decrypted = AesGcm::decrypt($ciphertext, $apiV3Key, $nonceValue, $associatedData);
        /** @var array<string, mixed> $resourceData */
        $resourceData = Json::decode($decrypted);

        $result = [
            'payload' => $payload,
            'resource' => $resourceData,
        ];

        $subMchId = $resourceData['sub_mchid'] ?? null;
        $this->logOperation(
            $merchant,
            \is_string($subMchId) ? $subMchId : null,
            ProfitShareOperationType::NOTIFICATION,
            true,
            null,
            null,
            $payload,
            $resourceData,
        );

        return $result;
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<string, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $key => $value) {
            $normalized[strtoupper($key)] = $value;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $request
     * @param array<string, mixed> $response
     */
    private function logOperation(
        Merchant $merchant,
        ?string $subMchId,
        ProfitShareOperationType $type,
        bool $success,
        ?string $errorCode,
        ?string $errorMessage,
        array $request,
        array $response
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
}
