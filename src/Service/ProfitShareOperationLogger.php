<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Service;

use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOperationLog;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOperationType;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOperationLogRepository;
use Tourze\WechatPayProfitShareBundle\Service\Helper\WechatPayProfitShareHelperTrait;
use WechatPayBundle\Entity\Merchant;
use Yiisoft\Json\Json;

/**
 * 分账操作日志记录器
 * 负责统一处理分账操作的日志记录和持久化
 */
final class ProfitShareOperationLogger
{
    use WechatPayProfitShareHelperTrait;

    public function __construct(
        private readonly ProfitShareOperationLogRepository $operationLogRepository,
    ) {
    }

    /**
     * 记录分账操作日志
     *
     * @param array<string, mixed>|string|null $request
     * @param array<string, mixed>|string|null $response
     */
    public function logOperation(
        Merchant $merchant,
        string $subMchId,
        ProfitShareOperationType $type,
        bool $success,
        ?string $errorCode,
        ?string $errorMessage,
        array|string|null $request,
        array|string|null $response,
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
     * 编码负载数据为字符串
     *
     * @param array<string, mixed>|string|null $payload
     */
    private function encodePayload(array|string|null $payload): ?string
    {
        if (null === $payload) {
            return null;
        }

        if (is_string($payload)) {
            return $payload;
        }

        return Json::encode($payload);
    }
}
