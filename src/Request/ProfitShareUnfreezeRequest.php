<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Request;

class ProfitShareUnfreezeRequest
{
    public function __construct(
        private ?string $subMchId = null,
        private ?string $transactionId = null,
        private ?string $outOrderNo = null,
        private ?string $description = '解冻剩余未分账资金',
        private bool $unfreezeUnsplit = true,
    ) {
    }

    public function getSubMchId(): ?string
    {
        return $this->subMchId;
    }

    public function setSubMchId(?string $subMchId): void
    {
        $this->subMchId = $subMchId;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(?string $transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    public function getOutOrderNo(): ?string
    {
        return $this->outOrderNo;
    }

    public function setOutOrderNo(?string $outOrderNo): void
    {
        $this->outOrderNo = $outOrderNo;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function isUnfreezeUnsplit(): bool
    {
        return $this->unfreezeUnsplit;
    }

    public function setUnfreezeUnsplit(bool $unfreezeUnsplit): void
    {
        $this->unfreezeUnsplit = $unfreezeUnsplit;
    }

    /**
     * @return array{
     *   sub_mchid: string|null,
     *   transaction_id: string|null,
     *   out_order_no: string|null,
     *   description: string|null,
     *   unfreeze_unsplit?: true
     * }
     */
    public function toPayload(): array
    {
        $payload = [
            'sub_mchid' => $this->subMchId,
            'transaction_id' => $this->transactionId,
            'out_order_no' => $this->outOrderNo,
            'description' => $this->description,
        ];

        if ($this->unfreezeUnsplit) {
            $payload['unfreeze_unsplit'] = true;
        }

        return $payload;
    }
}
