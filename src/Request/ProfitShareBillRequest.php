<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Request;

use DateTimeInterface;

class ProfitShareBillRequest
{
    public function __construct(
        private readonly DateTimeInterface $billDate,
        private readonly ?string $subMchId = null,
        private readonly ?string $tarType = null,
    ) {
    }

    public function getBillDate(): DateTimeInterface
    {
        return $this->billDate;
    }

    public function getSubMchId(): ?string
    {
        return $this->subMchId;
    }

    public function getTarType(): ?string
    {
        return $this->tarType;
    }

    /**
     * @return array{
     *   bill_date: string,
     *   sub_mchid?: string,
     *   tar_type?: string
     * }
     */
    public function toQuery(): array
    {
        $query = [
            'bill_date' => $this->billDate->format('Y-m-d'),
        ];

        if (null !== $this->subMchId && '' !== $this->subMchId) {
            $query['sub_mchid'] = $this->subMchId;
        }

        if (null !== $this->tarType && '' !== $this->tarType) {
            $query['tar_type'] = $this->tarType;
        }

        return $query;
    }
}
