<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareReceiverResult;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareReceiverRepository;

/**
 * @see https://pay.weixin.qq.com/doc/v3/partner/4012466868
 */
#[ORM\Entity(repositoryClass: ProfitShareReceiverRepository::class)]
#[ORM\Table(name: 'wechat_pay_profit_share_receiver', options: ['comment' => '微信支付分账接收方'])]
class ProfitShareReceiver implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use BlameableAware;

    #[ORM\ManyToOne(targetEntity: ProfitShareOrder::class, inversedBy: 'receivers', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ProfitShareOrder $order = null;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '接收方顺序'])]
    #[Assert\PositiveOrZero(message: '接收方顺序必须大于等于0')]
    private int $sequence = 0;

    #[ORM\Column(type: Types::STRING, length: 32, options: ['comment' => '接收方类型'])]
    #[Assert\NotBlank(message: '接收方类型不能为空')]
    #[Assert\Length(max: 32, maxMessage: '接收方类型长度不能超过 {{ limit }} 个字符')]
    private string $type;

    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => '接收方账号'])]
    #[Assert\NotBlank(message: '接收方账号不能为空')]
    #[Assert\Length(max: 64, maxMessage: '接收方账号长度不能超过 {{ limit }} 个字符')]
    private string $account;

    #[ORM\Column(type: Types::STRING, length: 1024, nullable: true, options: ['comment' => '接收方姓名密文'])]
    #[Assert\Length(max: 1024, maxMessage: '接收方姓名长度不能超过 {{ limit }} 个字符')]
    private ?string $name = null;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '分账金额(分)'])]
    #[Assert\NotNull(message: '分账金额不能为空')]
    #[Assert\Positive(message: '分账金额必须大于0')]
    private int $amount;

    #[ORM\Column(type: Types::STRING, length: 80, options: ['comment' => '分账描述'])]
    #[Assert\NotBlank(message: '分账描述不能为空')]
    #[Assert\Length(max: 80, maxMessage: '分账描述长度不能超过 {{ limit }} 个字符')]
    private string $description;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: ProfitShareReceiverResult::class, options: ['default' => 'PENDING', 'comment' => '分账结果'])]
    #[Assert\Choice(callback: [ProfitShareReceiverResult::class, 'cases'], message: '分账结果值无效')]
    private ProfitShareReceiverResult $result = ProfitShareReceiverResult::PENDING;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '分账失败原因'])]
    #[Assert\Length(max: 64, maxMessage: '分账失败原因长度不能超过 {{ limit }} 个字符')]
    private ?string $failReason = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '分账创建时间'])]
    #[Assert\DateTime(message: '分账创建时间必须是有效的日期时间')]
    private ?\DateTimeImmutable $wechatCreatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '分账完成时间'])]
    #[Assert\DateTime(message: '分账完成时间必须是有效的日期时间')]
    private ?\DateTimeImmutable $wechatFinishedAt = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '微信分账明细单号'])]
    #[Assert\Length(max: 64, maxMessage: '分账明细单号长度不能超过 {{ limit }} 个字符')]
    private ?string $detailId = null;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '重试次数', 'default' => 0])]
    #[Assert\PositiveOrZero(message: '重试次数必须大于等于0')]
    private int $retryCount = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '下次重试时间'])]
    #[Assert\DateTime(message: '下次重试时间必须是有效的日期时间')]
    private ?\DateTimeImmutable $nextRetryAt = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否最终失败', 'default' => false])]
    #[Assert\Type(type: 'bool', message: '是否最终失败必须是布尔值')]
    private bool $finallyFailed = false;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '扩展数据'])]
    #[Assert\Type(type: 'array', message: '扩展数据必须是数组类型')]
    private ?array $metadata = null;

    /**
     * @var string|null 接收方详情
     */
    #[Assert\Type(type: 'string', message: '接收方详情必须是字符串类型')]
    private ?string $detail = null;

    /**
     * @var int|null 完成金额
     */
    #[Assert\Type(type: 'int', message: '完成金额必须是整数类型')]
    #[Assert\PositiveOrZero(message: '完成金额必须大于等于0')]
    private ?int $finishAmount = null;

    public function __toString(): string
    {
        return $this->account ?? '';
    }

    public function getOrder(): ?ProfitShareOrder
    {
        return $this->order;
    }

    public function setOrder(?ProfitShareOrder $order): void
    {
        $this->order = $order;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function setSequence(int $sequence): void
    {
        $this->sequence = $sequence;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getAccount(): string
    {
        return $this->account;
    }

    public function setAccount(string $account): void
    {
        $this->account = $account;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getResult(): ProfitShareReceiverResult
    {
        return $this->result;
    }

    public function setResult(ProfitShareReceiverResult $result): void
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

    public function getDetailId(): ?string
    {
        return $this->detailId;
    }

    public function setDetailId(?string $detailId): void
    {
        $this->detailId = $detailId;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function setRetryCount(int $retryCount): void
    {
        $this->retryCount = $retryCount;
    }

    public function getNextRetryAt(): ?\DateTimeImmutable
    {
        return $this->nextRetryAt;
    }

    public function setNextRetryAt(?\DateTimeImmutable $nextRetryAt): void
    {
        $this->nextRetryAt = $nextRetryAt;
    }

    public function isFinallyFailed(): bool
    {
        return $this->finallyFailed;
    }

    public function setFinallyFailed(bool $finallyFailed): void
    {
        $this->finallyFailed = $finallyFailed;
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

    public function getDetail(): ?string
    {
        return $this->detail ?? null;
    }

    public function setDetail(?string $detail): void
    {
        $this->detail = $detail;
    }

    public function getFinishAmount(): int
    {
        return $this->finishAmount ?? 0;
    }

    public function setFinishAmount(int $finishAmount): void
    {
        $this->finishAmount = $finishAmount;
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
