<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Service;

use Psr\Log\LoggerInterface;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOperationLog;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReturnOrder;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOperationType;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOperationLogRepository;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareReturnOrderRepository;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReturnRequest;
use Tourze\WechatPayProfitShareBundle\Service\Helper\WechatPayProfitShareHelperTrait;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

class ProfitShareReturnService
{
    use WechatPayProfitShareHelperTrait;

    public function __construct(
        private readonly ProfitShareReturnOrderRepository $returnOrderRepository,
        private readonly ProfitShareOperationLogRepository $operationLogRepository,
        private readonly WechatPayBuilder $wechatPayBuilder,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function requestReturn(Merchant $merchant, ProfitShareReturnRequest $request): ProfitShareReturnOrder
    {
        $existing = $this->returnOrderRepository->findOneBy([
            'outReturnNo' => $request->getOutReturnNo(),
        ]);

        if (null !== $existing) {
            return $existing;
        }

        $entity = new ProfitShareReturnOrder();
        $entity->setMerchant($merchant);
        $entity->setSubMchId($request->getSubMchId());
        $entity->setOutReturnNo($request->getOutReturnNo());
        $entity->setOutOrderNo($request->getOutOrderNo());
        $entity->setOrderId($request->getOrderId());

        $payload = $request->toPayload();
        $entity->setRequestPayload($this->encodePayload($payload));

        $builder = $this->wechatPayBuilder->genBuilder($merchant);
        $segment = 'v3/profitsharing/return-orders';
        $startTime = microtime(true);

        try {
            $this->logger->info('发起微信分账回退请求', [
                'sub_mchid' => $payload['sub_mchid'],
                'out_return_no' => $payload['out_return_no'],
            ]);

            $response = $builder->chain($segment)->post([
                'json' => $payload,
            ]);
            $body = $response->getBody()->getContents();
            /** @var array<string, mixed> $responseData */
            $responseData = Json::decode($body);

            $this->logger->info('微信分账回退请求成功', [
                'out_return_no' => $payload['out_return_no'],
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            $this->applyReturnResponse($entity, $responseData);
            $entity->setResponsePayload($this->encodePayload($responseData));
            $this->returnOrderRepository->save($entity);

            $this->logOperation(
                $merchant,
                $payload['sub_mchid'],
                ProfitShareOperationType::REQUEST_RETURN,
                true,
                null,
                null,
                $payload,
                $responseData,
            );

            return $entity;
        } catch (\Throwable $exception) {
            $this->logger->error('微信分账回退出错', [
                'out_return_no' => $payload['out_return_no'],
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'exception' => $exception->getMessage(),
            ]);

            $this->logOperation(
                $merchant,
                $payload['sub_mchid'],
                ProfitShareOperationType::REQUEST_RETURN,
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

    /**
     * @param array<string, mixed> $response
     */
    private function applyReturnResponse(ProfitShareReturnOrder $entity, array $response): void
    {
        $this->applyOrderFields($entity, $response);
        $this->applyReturnFields($entity, $response);
        $this->applyResultFields($entity, $response);
        $this->applyAmountFields($entity, $response);
        $this->applyTimeFields($entity, $response);
    }

    /**
     * @param array<string, mixed> $response
     */
    private function applyOrderFields(ProfitShareReturnOrder $entity, array $response): void
    {
        if (isset($response['order_id']) && \is_string($response['order_id'])) {
            $entity->setOrderId($response['order_id']);
        }

        if (isset($response['out_order_no']) && \is_string($response['out_order_no'])) {
            $entity->setOutOrderNo($response['out_order_no']);
        }
    }

    /**
     * @param array<string, mixed> $response
     */
    private function applyReturnFields(ProfitShareReturnOrder $entity, array $response): void
    {
        if (isset($response['return_no']) && \is_string($response['return_no'])) {
            $entity->setReturnNo($response['return_no']);
        }

        if (isset($response['description']) && \is_string($response['description'])) {
            $entity->setDescription($response['description']);
        }
    }

    /**
     * @param array<string, mixed> $response
     */
    private function applyResultFields(ProfitShareReturnOrder $entity, array $response): void
    {
        if (isset($response['result']) && \is_string($response['result'])) {
            $entity->setResult($response['result']);
        }

        if (isset($response['fail_reason']) && \is_string($response['fail_reason'])) {
            $entity->setFailReason($response['fail_reason']);
        }
    }

    /**
     * @param array<string, mixed> $response
     */
    private function applyAmountFields(ProfitShareReturnOrder $entity, array $response): void
    {
        if (isset($response['amount'])) {
            $amount = $response['amount'];
            $entity->setAmount(\is_int($amount) ? $amount : 0);
        }
    }

    /**
     * @param array<string, mixed> $response
     */
    private function applyTimeFields(ProfitShareReturnOrder $entity, array $response): void
    {
        $createdAt = $this->parseWechatTime($response['create_time'] ?? null, $this->logger);
        if (null !== $createdAt) {
            $entity->setWechatCreatedAt($createdAt);
        }

        $finishedAt = $this->parseWechatTime($response['finish_time'] ?? null, $this->logger);
        if (null !== $finishedAt) {
            $entity->setWechatFinishedAt($finishedAt);
        }
    }

    /**
     * @param array<string, mixed> $request
     * @param array<string, mixed>|string|null $response
     */
    private function logOperation(
        Merchant $merchant,
        string|int|null $subMchId,
        ProfitShareOperationType $type,
        bool $success,
        ?string $errorCode,
        ?string $errorMessage,
        array $request,
        $response
    ): void {
        $log = new ProfitShareOperationLog();
        $log->setMerchant($merchant);
        $normalizedSubMchId = \is_string($subMchId) ? $subMchId : (\is_int($subMchId) ? (string) $subMchId : null);
        $log->setSubMchId($normalizedSubMchId);
        $log->setType($type);
        $log->setSuccess($success);
        $log->setErrorCode($errorCode);
        $log->setErrorMessage($errorMessage);
        $log->setRequestPayload($this->encodePayload($request));
        $log->setResponsePayload($this->encodePayload($response));

        $this->operationLogRepository->save($log);
    }

    private function extractErrorCode(\Throwable $exception): ?string
    {
        $code = $exception->getCode();

        return 0 === $code ? null : (string) $code;
    }

    public function queryReturn(
        Merchant $merchant,
        string $subMchId,
        string $outReturnNo,
        ?string $outOrderNo = null,
        ?string $orderId = null
    ): ProfitShareReturnOrder {
        if (null === $outOrderNo && null === $orderId) {
            throw new \InvalidArgumentException('商户分账单号和微信分账单号不能同时为空');
        }

        $entity = $this->returnOrderRepository->findOneBy(['outReturnNo' => $outReturnNo]);
        if (null === $entity) {
            $entity = new ProfitShareReturnOrder();
            $entity->setMerchant($merchant);
            $entity->setOutReturnNo($outReturnNo);
        }
        $entity->setSubMchId($subMchId);
        $entity->setOutOrderNo($outOrderNo);
        $entity->setOrderId($orderId);

        $query = [
            'sub_mchid' => $subMchId,
        ];
        if (null !== $outOrderNo && '' !== $outOrderNo) {
            $query['out_order_no'] = $outOrderNo;
        }
        if (null !== $orderId && '' !== $orderId) {
            $query['order_id'] = $orderId;
        }

        $builder = $this->wechatPayBuilder->genBuilder($merchant);
        $segment = sprintf(
            'v3/profitsharing/return-orders/%s?%s',
            rawurlencode($outReturnNo),
            http_build_query($query)
        );
        $startTime = microtime(true);

        try {
            $this->logger->info('查询微信分账回退结果', [
                'sub_mchid' => $subMchId,
                'out_return_no' => $outReturnNo,
            ]);

            $response = $builder->chain($segment)->get();
            $body = $response->getBody()->getContents();
            /** @var array<string, mixed> $responseData */
            $responseData = Json::decode($body);

            $this->logger->info('查询微信分账回退结果成功', [
                'out_return_no' => $outReturnNo,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            $this->applyReturnResponse($entity, $responseData);
            $entity->setResponsePayload($this->encodePayload($responseData));
            $this->returnOrderRepository->save($entity);

            $this->logOperation(
                $merchant,
                $subMchId,
                ProfitShareOperationType::QUERY_RETURN,
                true,
                null,
                null,
                $query,
                $responseData,
            );

            return $entity;
        } catch (\Throwable $exception) {
            $this->logger->error('查询微信分账回退结果失败', [
                'out_return_no' => $outReturnNo,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'exception' => $exception->getMessage(),
            ]);

            $this->logOperation(
                $merchant,
                $subMchId,
                ProfitShareOperationType::QUERY_RETURN,
                false,
                $this->extractErrorCode($exception),
                $exception->getMessage(),
                $query,
                ['exception' => $exception->getMessage()],
            );

            throw $exception;
        }
    }
}
