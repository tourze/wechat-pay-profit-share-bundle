<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Service;

use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReceiver;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareOrderRequest;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReceiverRequest;
use WechatPayBundle\Entity\Merchant;

/**
 * 分账订单工厂
 * 负责创建分账订单和接收方实体
 */
class ProfitShareOrderFactory
{
    /**
     * 构建分账订单实体
     */
    public function buildOrderEntity(Merchant $merchant, ProfitShareOrderRequest $request): ProfitShareOrder
    {
        $order = new ProfitShareOrder();
        $order->setMerchant($merchant);
        $order->setSubMchId($request->getSubMchId());
        $order->setAppId($request->getAppId());
        $order->setSubAppId($request->getSubAppId());
        $order->setTransactionId($request->getTransactionId());
        $order->setOutOrderNo($request->getOutOrderNo());
        $order->setUnfreezeUnsplit($request->isUnfreezeUnsplit());

        foreach ($request->getReceivers() as $index => $receiverRequest) {
            $receiver = $this->buildReceiverEntity($receiverRequest, $index);
            $order->addReceiver($receiver);
        }

        return $order;
    }

    /**
     * 从响应数据构建分账订单实体
     *
     * @param Merchant $merchant 商户
     * @param array<string, mixed> $responseData 响应数据
     */
    public function buildOrderFromResponse(Merchant $merchant, array $responseData): ProfitShareOrder
    {
        $order = new ProfitShareOrder();
        $order->setMerchant($merchant);

        // 处理必填字符串字段
        $subMchId = $responseData['sub_mchid'] ?? '';
        assert(is_string($subMchId));
        $order->setSubMchId($subMchId);

        $transactionId = $responseData['transaction_id'] ?? '';
        assert(is_string($transactionId));
        $order->setTransactionId($transactionId);

        $outOrderNo = $responseData['out_order_no'] ?? '';
        assert(is_string($outOrderNo));
        $order->setOutOrderNo($outOrderNo);

        // 处理可空字符串字段
        $appId = $responseData['appid'] ?? null;
        assert(is_string($appId) || null === $appId);
        $order->setAppId($appId);

        $subAppId = $responseData['sub_appid'] ?? null;
        assert(is_string($subAppId) || null === $subAppId);
        $order->setSubAppId($subAppId);

        $orderId = $responseData['order_id'] ?? null;
        assert(is_string($orderId) || null === $orderId);
        $order->setOrderId($orderId);

        return $order;
    }

    /**
     * 构建接收方实体
     */
    private function buildReceiverEntity(ProfitShareReceiverRequest $request, int $index): ProfitShareReceiver
    {
        $receiver = new ProfitShareReceiver();
        $receiver->setSequence($index);
        $receiver->setType($request->getType());
        $receiver->setAccount($request->getAccount());
        $receiver->setAmount($request->getAmount());
        $receiver->setDescription($request->getDescription());
        $receiver->setName($request->getName());

        return $receiver;
    }
}
