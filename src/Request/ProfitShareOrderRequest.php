<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Request;

final class ProfitShareOrderRequest
{
    /**
     * @var list<ProfitShareReceiverRequest>
     */
    private array $receivers = [];

    private ?string $appId = null;

    private ?string $subAppId = null;

    private bool $unfreezeUnsplit = false;

    public function __construct(
        private readonly string $subMchId,
        private readonly string $transactionId,
        private readonly string $outOrderNo,
    ) {
    }

    public function getSubMchId(): string
    {
        return $this->subMchId;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getOutOrderNo(): string
    {
        return $this->outOrderNo;
    }

    public function getAppId(): ?string
    {
        return $this->appId;
    }

    public function setAppId(?string $appId): void
    {
        $this->appId = $appId;
    }

    public function getSubAppId(): ?string
    {
        return $this->subAppId;
    }

    public function setSubAppId(?string $subAppId): void
    {
        $this->subAppId = $subAppId;
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
     * @return list<ProfitShareReceiverRequest>
     */
    public function getReceivers(): array
    {
        return $this->receivers;
    }

    /**
     * @param list<ProfitShareReceiverRequest> $receivers
     */
    public function setReceivers(array $receivers): void
    {
        $this->receivers = $receivers;
    }

    public function addReceiver(ProfitShareReceiverRequest $receiver): void
    {
        $this->receivers[] = $receiver;
    }

    /**
     * @return array{
     *   sub_mchid: string,
     *   transaction_id: string,
     *   out_order_no: string,
     *   unfreeze_unsplit: bool,
     *   receivers: list<array<string, int|string>>,
     *   appid?: string,
     *   sub_appid?: string
     * }
     */
    public function toPayload(): array
    {
        $payload = [
            'sub_mchid' => $this->subMchId,
            'transaction_id' => $this->transactionId,
            'out_order_no' => $this->outOrderNo,
            'unfreeze_unsplit' => $this->unfreezeUnsplit,
            'receivers' => array_map(
                static fn (ProfitShareReceiverRequest $receiver): array => $receiver->toPayload(),
                $this->receivers,
            ),
        ];

        if (null !== $this->appId && '' !== $this->appId) {
            $payload['appid'] = $this->appId;
        }

        if (null !== $this->subAppId && '' !== $this->subAppId) {
            $payload['sub_appid'] = $this->subAppId;
        }

        return $payload;
    }
}
