<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Request;

final class ProfitShareReceiverAddRequest
{
    public function __construct(
        private readonly string $subMchId,
        private readonly string $appid,
        private readonly string $type,
        private readonly string $account,
        private readonly string $relationType,
        private readonly ?string $name = null,
        private readonly ?string $subAppid = null,
        private readonly ?string $customRelation = null,
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getRelationType(): string
    {
        return $this->relationType;
    }

    public function getCustomRelation(): ?string
    {
        return $this->customRelation;
    }

    /**
     * @return array{
     *   sub_mchid: string,
     *   appid: string,
     *   type: string,
     *   account: string,
     *   relation_type: string,
     *   sub_appid?: string,
     *   name?: string,
     *   custom_relation?: string
     * }
     */
    public function toPayload(): array
    {
        $payload = [
            'sub_mchid' => $this->subMchId,
            'appid' => $this->appid,
            'type' => $this->type,
            'account' => $this->account,
            'relation_type' => $this->relationType,
        ];

        if (null !== $this->subAppid && '' !== $this->subAppid) {
            $payload['sub_appid'] = $this->subAppid;
        }

        if (null !== $this->name && '' !== $this->name) {
            $payload['name'] = $this->name;
        }

        if (null !== $this->customRelation && '' !== $this->customRelation) {
            $payload['custom_relation'] = $this->customRelation;
        }

        return $payload;
    }
}
