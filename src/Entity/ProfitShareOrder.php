<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOrderState;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOrderRepository;
use WechatPayBundle\Entity\Merchant;

#[ORM\Entity(repositoryClass: ProfitShareOrderRepository::class)]
#[ORM\Table(name: 'wechat_pay_profit_share_order', options: ['comment' => '微信支付分账订单'])]
class ProfitShareOrder implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use BlameableAware;

    #[ORM\ManyToOne(targetEntity: Merchant::class)]
    private ?Merchant $merchant = null;

    #[ORM\Column(type: Types::STRING, length: 32, options: ['comment' => '特约商户号'])]
    #[Assert\NotBlank(message: '特约商户号不能为空')]
    #[Assert\Length(max: 32, maxMessage: '特约商户号长度不能超过 {{ limit }} 个字符')]
    private string $subMchId;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '公众账号ID'])]
    #[Assert\Length(max: 32, maxMessage: '公众账号ID长度不能超过 {{ limit }} 个字符')]
    private ?string $appId = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '特约商户公众账号ID'])]
    #[Assert\Length(max: 32, maxMessage: '特约商户公众账号ID长度不能超过 {{ limit }} 个字符')]
    private ?string $subAppId = null;

    #[ORM\Column(type: Types::STRING, length: 32, options: ['comment' => '微信支付订单号'])]
    #[Assert\NotBlank(message: '微信支付订单号不能为空')]
    #[Assert\Length(max: 32, maxMessage: '微信支付订单号长度不能超过 {{ limit }} 个字符')]
    private string $transactionId;

    #[ORM\Column(type: Types::STRING, length: 64, unique: true, options: ['comment' => '商户分账单号'])]
    #[Assert\NotBlank(message: '商户分账单号不能为空')]
    #[Assert\Length(max: 64, maxMessage: '商户分账单号长度不能超过 {{ limit }} 个字符')]
    private string $outOrderNo;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '微信分账单号'])]
    #[Assert\Length(max: 64, maxMessage: '微信分账单号长度不能超过 {{ limit }} 个字符')]
    private ?string $orderId = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: ProfitShareOrderState::class, options: ['default' => 'PROCESSING', 'comment' => '分账状态'])]
    #[Assert\Choice(callback: [ProfitShareOrderState::class, 'cases'], message: '分账状态值无效')]
    private ProfitShareOrderState $state = ProfitShareOrderState::PROCESSING;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否解冻剩余未分资金'])]
    #[Assert\Type(type: 'bool', message: '是否解冻剩余未分资金必须是布尔值')]
    private bool $unfreezeUnsplit = false;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '请求JSON'])]
    #[Assert\Json(message: '请求JSON必须是合法的JSON格式')]
    private ?string $requestPayload = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '响应JSON'])]
    #[Assert\Json(message: '响应JSON必须是合法的JSON格式')]
    private ?string $responsePayload = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '分账创建时间(RFC3339)'])]
    #[Assert\DateTime(message: '分账创建时间必须是有效的日期时间')]
    private ?\DateTimeImmutable $wechatCreatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '分账完成时间(RFC3339)'])]
    #[Assert\DateTime(message: '分账完成时间必须是有效的日期时间')]
    private ?\DateTimeImmutable $wechatFinishedAt = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '扩展数据'])]
    #[Assert\Type(type: 'array', message: '扩展数据必须是数组类型')]
    private ?array $metadata = null;

    /**
     * @var string|null 完成时间
     */
    #[Assert\Type(type: 'string', message: '完成时间必须是字符串类型')]
    private ?string $finishTime = null;

    /**
     * @var string|null 成功时间
     */
    #[Assert\Type(type: 'string', message: '成功时间必须是字符串类型')]
    private ?string $successTime = null;

    /**
     * @var Collection<int, ProfitShareReceiver>
     */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: ProfitShareReceiver::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $receivers;

    public function __construct()
    {
        $this->receivers = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->outOrderNo ?? '';
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

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function setTransactionId(string $transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    public function getOutOrderNo(): string
    {
        return $this->outOrderNo;
    }

    public function setOutOrderNo(string $outOrderNo): void
    {
        $this->outOrderNo = $outOrderNo;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getState(): ProfitShareOrderState
    {
        return $this->state;
    }

    public function setState(ProfitShareOrderState $state): void
    {
        $this->state = $state;
    }

    public function isUnfreezeUnsplit(): bool
    {
        return $this->unfreezeUnsplit;
    }

    public function setUnfreezeUnsplit(bool $unfreezeUnsplit): void
    {
        $this->unfreezeUnsplit = $unfreezeUnsplit;
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

    /**
     * @return Collection<int, ProfitShareReceiver>
     */
    public function getReceivers(): Collection
    {
        return $this->receivers;
    }

    public function addReceiver(ProfitShareReceiver $receiver): void
    {
        if (!$this->receivers->contains($receiver)) {
            $this->receivers->add($receiver);
            $receiver->setOrder($this);
        }
    }

    public function removeReceiver(ProfitShareReceiver $receiver): void
    {
        if ($this->receivers->removeElement($receiver)) {
            if ($receiver->getOrder() === $this) {
                $receiver->setOrder(null);
            }
        }
    }

    public function clearReceivers(): void
    {
        foreach ($this->receivers as $receiver) {
            $receiver->setOrder(null);
        }
        $this->receivers->clear();
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

    public function getFinishTime(): ?string
    {
        return $this->finishTime;
    }

    public function setFinishTime(?string $finishTime): void
    {
        $this->finishTime = $finishTime;
    }

    public function getSuccessTime(): ?string
    {
        return $this->successTime;
    }

    public function setSuccessTime(?string $successTime): void
    {
        $this->successTime = $successTime;
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
