<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Service;

use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReceiver;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOrderState;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareReceiverResult;

/**
 * 分账响应处理器
 * 负责处理微信分账响应数据的同步和应用
 */
final class ProfitShareResponseProcessor
{
    /**
     * 应用响应数据到订单
     *
     * @param array<string, mixed> $response
     */
    public function applyResponse(ProfitShareOrder $order, array $response): void
    {
        $this->applyOrderMeta($order, $response);

        if (!isset($response['receivers']) || !is_array($response['receivers'])) {
            return;
        }

        /** @var array<array<string, mixed>> $receiverData */
        $receiverData = $response['receivers'];
        $receiverRows = $this->prepareReceiverRows($receiverData);
        if ([] === $receiverRows) {
            return;
        }

        $receiverIndex = $this->indexReceivers($order);
        $this->syncReceivers($order, $receiverRows, $receiverIndex);
        $this->updateOrderTimeline($order, $receiverRows);
    }

    /**
     * 应用订单元数据
     *
     * @param array<string, mixed> $response
     */
    private function applyOrderMeta(ProfitShareOrder $order, array $response): void
    {
        $orderState = $response['state'] ?? '';
        if ('PROCESSING' === $orderState) {
            $order->setState(ProfitShareOrderState::PROCESSING);
        } elseif ('FINISHED' === $orderState) {
            $order->setState(ProfitShareOrderState::FINISHED);
        } elseif ('CLOSED' === $orderState) {
            $order->setState(ProfitShareOrderState::CLOSED);
        }

        if (isset($response['transaction_id'])) {
            $transactionId = $response['transaction_id'];
            if (is_string($transactionId)) {
                $order->setTransactionId($transactionId);
            }
        }

        $orderId = $response['order_id'] ?? '';
        $order->setOrderId(is_string($orderId) ? $orderId : null);

        $outOrderNo = $response['out_order_no'] ?? '';
        $order->setOutOrderNo(is_string($outOrderNo) ? $outOrderNo : '');
    }

    /**
     * 准备接收方数据
     *
     * @param array<array<string, mixed>> $receivers
     * @return array<array<string, mixed>>
     */
    private function prepareReceiverRows(array $receivers): array
    {
        $result = [];
        foreach ($receivers as $receiver) {
            if (!isset($receiver['type'], $receiver['account'], $receiver['amount'])) {
                continue;
            }

            $key = $this->makeReceiverKey($receiver);
            $result[$key] = $receiver;
        }

        return $result;
    }

    /**
     * 为接收方创建索引
     *
     * @return array<string, ProfitShareReceiver>
     */
    private function indexReceivers(ProfitShareOrder $order): array
    {
        $index = [];
        foreach ($order->getReceivers() as $receiver) {
            $key = $this->makeReceiverKeyFromEntity($receiver);
            $index[$key] = $receiver;
        }

        return $index;
    }

    /**
     * 同步接收方状态
     *
     * @param array<array<string, mixed>> $receiverRows
     * @param array<string, ProfitShareReceiver> $receiverIndex
     */
    private function syncReceivers(ProfitShareOrder $order, array $receiverRows, array $receiverIndex): void
    {
        foreach ($receiverRows as $key => $receiverRow) {
            $amount = $receiverRow['amount'] ?? 0;
            $result = $receiverRow['result'] ?? '';
            $amountInt = is_int($amount) ? $amount : 0;
            $resultStr = is_string($result) ? $result : '';

            if (isset($receiverIndex[$key])) {
                $receiverEntity = $receiverIndex[$key];
                $receiverEntity->setDetail($this->encodePayload($receiverRow));
                $receiverEntity->setFinishAmount($amountInt);

                if ('SUCCESS' === $resultStr) {
                    $receiverEntity->setResult(ProfitShareReceiverResult::SUCCESS);
                } elseif ('CLOSED' === $resultStr) {
                    $receiverEntity->setResult(ProfitShareReceiverResult::CLOSED);
                } elseif ('FAILED' === $resultStr) {
                    $receiverEntity->setResult(ProfitShareReceiverResult::FAILED);
                }
            }
        }
    }

    /**
     * 更新订单时间线
     *
     * @param array<array<string, mixed>> $receiverRows
     */
    private function updateOrderTimeline(ProfitShareOrder $order, array $receiverRows): void
    {
        $allFinishTimes = [];
        $allSuccessTimes = [];

        foreach ($receiverRows as $receiverRow) {
            $finishTime = $receiverRow['finish_time'] ?? '';
            $result = $receiverRow['result'] ?? '';
            $finishTimeStr = is_string($finishTime) ? $finishTime : '';
            $resultStr = is_string($result) ? $result : '';

            if ('' !== $finishTimeStr) {
                $allFinishTimes[] = $finishTimeStr;
                if ('SUCCESS' === $resultStr) {
                    $allSuccessTimes[] = $finishTimeStr;
                }
            }
        }

        if (count($allFinishTimes) > 0) {
            $order->setFinishTime($this->maxDate($allFinishTimes));
        }

        if (count($allSuccessTimes) > 0) {
            $order->setSuccessTime($this->minDate($allSuccessTimes));
        }
    }

    /**
     * 从接收方数据生成唯一键
     *
     * @param array<string, mixed> $receiverRow
     */
    private function makeReceiverKey(array $receiverRow): string
    {
        $type = $receiverRow['type'] ?? '';
        $account = $receiverRow['account'] ?? '';
        $amount = $receiverRow['amount'] ?? 0;

        $typeStr = is_string($type) ? $type : '';
        $accountStr = is_string($account) ? $account : '';
        $amountInt = is_int($amount) ? $amount : 0;

        return sprintf('%s|%s|%d', $typeStr, $accountStr, $amountInt);
    }

    /**
     * 从接收方实体生成唯一键
     */
    private function makeReceiverKeyFromEntity(ProfitShareReceiver $receiver): string
    {
        return sprintf(
            '%s|%s|%d',
            $receiver->getType(),
            $receiver->getAccount(),
            $receiver->getAmount()
        );
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

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

        return false === $json ? null : $json;
    }

    /**
     * 获取最小日期
     *
     * @param array<string> $dates
     */
    private function minDate(array $dates): string
    {
        $minDate = null;
        foreach ($dates as $date) {
            if (null === $minDate || $date < $minDate) {
                $minDate = $date;
            }
        }

        return $minDate ?? '';
    }

    /**
     * 获取最大日期
     *
     * @param array<string> $dates
     */
    private function maxDate(array $dates): string
    {
        $maxDate = null;
        foreach ($dates as $date) {
            if (null === $maxDate || $date > $maxDate) {
                $maxDate = $date;
            }
        }

        return $maxDate ?? '';
    }
}
