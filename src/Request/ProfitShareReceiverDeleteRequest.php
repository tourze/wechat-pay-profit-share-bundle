<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Request;

class ProfitShareReceiverDeleteRequest
{
    public function __construct(
        private readonly string $subMchId,
        private readonly string $appid,
        private readonly string $type,
        private readonly string $account,
        private readonly ?string $subAppid = null,
    ) {
    }

    public function getSubMchId(): string
    {
        return $this->subMchId;
    }

    public function getAppid(): string
    {
        return $this->appid;
    }

    public function getSubAppid(): ?string
    {
        return $this->subAppid;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAccount(): string
    {
        return $this->account;
    }

    /**
     * @return array{
     *   sub_mchid: string,
     *   appid: string,
     *   type: string,
     *   account: string,
     *   sub_appid?: string
     * }
     */
    public function toPayload(): array
    {
        $payload = [
            'sub_mchid' => $this->subMchId,
            'appid' => $this->appid,
            'type' => $this->type,
            'account' => $this->account,
        ];

        if (null !== $this->subAppid && '' !== $this->subAppid) {
            $payload['sub_appid'] = $this->subAppid;
        }

        return $payload;
    }
}
