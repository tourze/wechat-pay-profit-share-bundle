<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Service;

use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOperationType;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOrderRepository;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareOrderRequest;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareUnfreezeRequest;
use Tourze\WechatPayProfitShareBundle\Service\Helper\WechatPayProfitShareHelperTrait;
use WechatPayBundle\Entity\Merchant;
use Yiisoft\Json\Json;

/**
 * 分账服务 - 重构后的简化版本
 * 负责分账业务的核心编排
 */
class ProfitShareService
{
    use WechatPayProfitShareHelperTrait;

    public function __construct(
        private readonly ProfitShareOrderRepository $orderRepository,
        private readonly ProfitShareApiExecutor $apiExecutor,
        private readonly ProfitShareOperationLogger $operationLogger,
        private readonly ProfitShareOrderFactory $orderFactory,
        private readonly ProfitShareResponseProcessor $responseProcessor,
    ) {
    }

    public function requestProfitShare(Merchant $merchant, ProfitShareOrderRequest $request): ProfitShareOrder
    {
        if (0 === count($request->getReceivers())) {
            throw new \InvalidArgumentException('分账请求至少需要一个接收方');
        }

        $existing = $this->orderRepository->findOneBy([
            'outOrderNo' => $request->getOutOrderNo(),
        ]);

        if (null !== $existing) {
            return $existing;
        }

        $order = $this->orderFactory->buildOrderEntity($merchant, $request);
        $payload = $request->toPayload();
        $order->setRequestPayload($this->encodePayload($payload));

        $segment = 'v3/profitsharing/orders';

        try {
            $responseData = $this->apiExecutor->executeRequest($merchant, $segment, $payload);

            $order->setResponsePayload($this->encodePayload($responseData));
            $this->responseProcessor->applyResponse($order, $responseData);
            $this->orderRepository->save($order);

            $this->operationLogger->logOperation(
                $merchant,
                $order->getSubMchId(),
                ProfitShareOperationType::REQUEST_ORDER,
                true,
                null,
                null,
                $payload,
                $responseData,
            );

            return $order;
        } catch (\Throwable $exception) {
            $this->operationLogger->logOperation(
                $merchant,
                $order->getSubMchId(),
                ProfitShareOperationType::REQUEST_ORDER,
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
     * 查询分账订单
     *
     * @param Merchant $merchant 商户
     * @param string|null $subMchId 特约商户号（可选）
     * @param string $outOrderNo 商户分账单号
     * @param string|null $transactionId 微信支付订单号（可选）
     * @return ProfitShareOrder 分账订单实体
     * @throws \Throwable
     */
    public function queryProfitShareOrder(
        Merchant $merchant,
        ?string $subMchId,
        string $outOrderNo,
        ?string $transactionId = null,
    ): ProfitShareOrder {
        $query = [
            'sub_mchid' => $subMchId,
        ];

        if (null !== $transactionId && '' !== $transactionId) {
            $query['transaction_id'] = $transactionId;
        }

        $segment = sprintf('v3/profitsharing/orders/%s', $outOrderNo);

        try {
            $responseData = $this->apiExecutor->executeQuery($merchant, $segment, $query);

            $order = $this->orderRepository->findOneBy(['outOrderNo' => $outOrderNo])
                ?? $this->orderFactory->buildOrderFromResponse($merchant, $responseData);

            $order->setResponsePayload($this->encodePayload($responseData));
            $this->responseProcessor->applyResponse($order, $responseData);
            $this->orderRepository->save($order);

            $this->operationLogger->logOperation(
                $merchant,
                $subMchId ?? '',
                ProfitShareOperationType::QUERY_ORDER,
                true,
                null,
                null,
                $query,
                $responseData,
            );

            return $order;
        } catch (\Throwable $exception) {
            $this->operationLogger->logOperation(
                $merchant,
                $subMchId ?? '',
                ProfitShareOperationType::QUERY_ORDER,
                false,
                $this->extractErrorCode($exception),
                $exception->getMessage(),
                $query,
                ['exception' => $exception->getMessage()],
            );

            throw $exception;
        }
    }

    /**
     * 解冻剩余资金
     *
     * @param Merchant $merchant 商户
     * @param ProfitShareUnfreezeRequest $request 解冻请求
     * @return ProfitShareOrder 分账订单实体
     * @throws \Throwable
     */
    public function unfreezeRemainingAmount(
        Merchant $merchant,
        ProfitShareUnfreezeRequest $request,
    ): ProfitShareOrder {
        $payload = $request->toPayload();
        $segment = 'v3/profitsharing/orders/unfreeze';

        try {
            $responseData = $this->apiExecutor->executeRequest($merchant, $segment, $payload);

            $order = $this->orderRepository->findOneBy(['outOrderNo' => $request->getOutOrderNo()])
                ?? $this->orderFactory->buildOrderFromResponse($merchant, $responseData);

            $order->setResponsePayload($this->encodePayload($responseData));
            $this->responseProcessor->applyResponse($order, $responseData);
            $this->orderRepository->save($order);

            $this->operationLogger->logOperation(
                $merchant,
                $request->getSubMchId() ?? '',
                ProfitShareOperationType::UNFREEZE_ORDER,
                true,
                null,
                null,
                $payload,
                $responseData,
            );

            return $order;
        } catch (\Throwable $exception) {
            $this->operationLogger->logOperation(
                $merchant,
                $request->getSubMchId() ?? '',
                ProfitShareOperationType::UNFREEZE_ORDER,
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
     * 编码载荷数据
     *
     * @param array<string, mixed>|string|null $payload
     */
    private function encodePayload($payload): ?string
    {
        if (null === $payload || '' === $payload) {
            return null;
        }

        if (is_string($payload)) {
            return $payload;
        }

        return Json::encode($payload);
    }

    /**
     * 提取错误码
     */
    private function extractErrorCode(\Throwable $exception): ?string
    {
        $code = $exception->getCode();

        return 0 === $code ? null : (string) $code;
    }
}
