<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Request;

final class ProfitShareReturnRequest
{
    public function __construct(
        private readonly string $subMchId,
        private readonly string $outReturnNo,
        private readonly int $amount,
        private readonly string $description,
        private readonly ?string $orderId = null,
        private readonly ?string $outOrderNo = null,
    ) {
        if (null === $this->orderId && null === $this->outOrderNo) {
            throw new \InvalidArgumentException('微信分账单号和商户分账单号不能同时为空');
        }
    }

    public function getSubMchId(): string
    {
        return $this->subMchId;
    }

    public function getOutReturnNo(): string
    {
        return $this->outReturnNo;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function getOutOrderNo(): ?string
    {
        return $this->outOrderNo;
    }

    /**
     * @return array{
     *   sub_mchid: string,
     *   out_return_no: string,
     *   amount: int,
     *   description: string,
     *   order_id?: string,
     *   out_order_no?: string
     * }
     */
    public function toPayload(): array
    {
        $payload = [
            'sub_mchid' => $this->subMchId,
            'out_return_no' => $this->outReturnNo,
            'amount' => $this->amount,
            'description' => $this->description,
        ];

        if (null !== $this->orderId && '' !== $this->orderId) {
            $payload['order_id'] = $this->orderId;
        }

        if (null !== $this->outOrderNo && '' !== $this->outOrderNo) {
            $payload['out_order_no'] = $this->outOrderNo;
        }

        return $payload;
    }
}
