<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareReturnOrderRepository;
use WechatPayBundle\Entity\Merchant;

/**
 * @see https://pay.weixin.qq.com/doc/v3/partner/4012466854
 */
#[ORM\Entity(repositoryClass: ProfitShareReturnOrderRepository::class)]
#[ORM\Table(name: 'wechat_pay_profit_share_return_order', options: ['comment' => '微信支付-分账回退单'])]
#[ORM\UniqueConstraint(name: 'uniq_profit_share_return_out_return_no', columns: ['out_return_no'])]
class ProfitShareReturnOrder implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use BlameableAware;

    #[ORM\ManyToOne(targetEntity: Merchant::class)]
    private ?Merchant $merchant = null;

    #[ORM\Column(type: Types::STRING, length: 32, options: ['comment' => '特约商户号'])]
    #[Assert\NotBlank(message: '特约商户号不能为空')]
    private string $subMchId;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '微信分账单号'])]
    #[Assert\Length(max: 64, maxMessage: '微信分账单号长度不能超过 {{ limit }} 个字符')]
    private ?string $orderId = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '商户分账单号'])]
    #[Assert\Length(max: 64, maxMessage: '商户分账单号长度不能超过 {{ limit }} 个字符')]
    private ?string $outOrderNo = null;

    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => '商户回退单号'])]
    #[Assert\NotBlank(message: '商户回退单号不能为空')]
    #[Assert\Length(max: 64, maxMessage: '商户回退单号长度不能超过 {{ limit }} 个字符')]
    private string $outReturnNo;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '微信回退单号'])]
    #[Assert\Length(max: 64, maxMessage: '微信回退单号长度不能超过 {{ limit }} 个字符')]
    private ?string $returnNo = null;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '回退金额（分）'])]
    #[Assert\PositiveOrZero(message: '回退金额必须大于等于0')]
    private int $amount = 0;

    #[ORM\Column(type: Types::STRING, length: 80, nullable: true, options: ['comment' => '回退描述'])]
    #[Assert\Length(max: 80, maxMessage: '回退描述长度不能超过 {{ limit }} 个字符')]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true, options: ['comment' => '回退结果'])]
    #[Assert\Length(max: 20, maxMessage: '回退结果长度不能超过 {{ limit }} 个字符')]
    private ?string $result = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '失败原因'])]
    #[Assert\Length(max: 64, maxMessage: '失败原因长度不能超过 {{ limit }} 个字符')]
    private ?string $failReason = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '微信创建时间'])]
    #[Assert\DateTime(message: '微信创建时间必须是有效的日期时间')]
    private ?\DateTimeImmutable $wechatCreatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '微信完成时间'])]
    #[Assert\DateTime(message: '微信完成时间必须是有效的日期时间')]
    private ?\DateTimeImmutable $wechatFinishedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '请求负载'])]
    #[Assert\Json(message: '请求负载必须是合法的JSON格式')]
    private ?string $requestPayload = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '响应负载'])]
    #[Assert\Json(message: '响应负载必须是合法的JSON格式')]
    private ?string $responsePayload = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '扩展数据'])]
    #[Assert\Type(type: 'array', message: '扩展数据必须是数组类型')]
    private ?array $metadata = null;

    public function __toString(): string
    {
        return $this->outReturnNo ?? '';
    }

    public function getMerchant(): ?Merchant
    {
        return $this->merchant;
    }

    public function setMerchant(?Merchant $merchant): void
    {
        $this->merchant = $merchant;
    }

    public function getSubMchId(): string
    {
        return $this->subMchId;
    }

    public function setSubMchId(string $subMchId): void
    {
        $this->subMchId = $subMchId;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getOutOrderNo(): ?string
    {
        return $this->outOrderNo;
    }

    public function setOutOrderNo(?string $outOrderNo): void
    {
        $this->outOrderNo = $outOrderNo;
    }

    public function getOutReturnNo(): string
    {
        return $this->outReturnNo;
    }

    public function setOutReturnNo(string $outReturnNo): void
    {
        $this->outReturnNo = $outReturnNo;
    }

    public function getReturnNo(): ?string
    {
        return $this->returnNo;
    }

    public function setReturnNo(?string $returnNo): void
    {
        $this->returnNo = $returnNo;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setResult(?string $result): void
    {
        $this->result = $result;
    }

    public function getFailReason(): ?string
    {
        return $this->failReason;
    }

    public function setFailReason(?string $failReason): void
    {
        $this->failReason = $failReason;
    }

    public function getWechatCreatedAt(): ?\DateTimeImmutable
    {
        return $this->wechatCreatedAt;
    }

    public function setWechatCreatedAt(?\DateTimeImmutable $wechatCreatedAt): void
    {
        $this->wechatCreatedAt = $wechatCreatedAt;
    }

    public function getWechatFinishedAt(): ?\DateTimeImmutable
    {
        return $this->wechatFinishedAt;
    }

    public function setWechatFinishedAt(?\DateTimeImmutable $wechatFinishedAt): void
    {
        $this->wechatFinishedAt = $wechatFinishedAt;
    }

    public function getRequestPayload(): ?string
    {
        return $this->requestPayload;
    }

    public function setRequestPayload(?string $requestPayload): void
    {
        $this->requestPayload = $requestPayload;
    }

    public function getResponsePayload(): ?string
    {
        return $this->responsePayload;
    }

    public function setResponsePayload(?string $responsePayload): void
    {
        $this->responsePayload = $responsePayload;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function setMetadata(?array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createTime ?? null;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updateTime ?? null;
    }
}
