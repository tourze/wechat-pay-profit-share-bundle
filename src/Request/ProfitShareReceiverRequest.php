<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Request;

final class ProfitShareReceiverRequest
{
    public function __construct(
        private readonly string $type,
        private readonly string $account,
        private readonly int $amount,
        private readonly string $description,
        private readonly ?string $name = null,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAccount(): string
    {
        return $this->account;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return array{
     *   type: string,
     *   account: string,
     *   amount: int,
     *   description: string,
     *   name?: string
     * }
     */
    public function toPayload(): array
    {
        $payload = [
            'type' => $this->type,
            'account' => $this->account,
            'amount' => $this->amount,
            'description' => $this->description,
        ];

        if (null !== $this->name && '' !== $this->name) {
            $payload['name'] = $this->name;
        }

        return $payload;
    }
}
