<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareBillStatus;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareBillTaskRepository;
use WechatPayBundle\Entity\Merchant;

/**
 * @see https://pay.weixin.qq.com/doc/v3/partner/4012761131
 */
#[ORM\Entity(repositoryClass: ProfitShareBillTaskRepository::class)]
#[ORM\Table(name: 'wechat_pay_profit_share_bill_task', options: ['comment' => '微信支付-资金账单'])]
#[ORM\UniqueConstraint(name: 'uniq_profit_share_bill_task', columns: ['sub_mch_id', 'bill_date', 'tar_type'])]
class ProfitShareBillTask implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use BlameableAware;

    #[ORM\ManyToOne(targetEntity: Merchant::class)]
    private ?Merchant $merchant = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '特约商户号'])]
    #[Assert\NotNull(message: '特约商户号不能为空')]
    #[Assert\Length(min: 1, max: 32, minMessage: '特约商户号长度不能少于{{ limit }}个字符', maxMessage: '特约商户号长度不能超过{{ limit }}个字符')]
    private ?string $subMchId = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['comment' => '账单日期'])]
    #[Assert\NotNull(message: '账单日期不能为空')]
    private \DateTimeImmutable $billDate;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true, options: ['comment' => '压缩类型'])]
    #[Assert\Length(max: 10, maxMessage: '压缩类型长度不能超过{{ limit }}个字符')]
    private ?string $tarType = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true, options: ['comment' => '哈希类型'])]
    #[Assert\Length(max: 10, maxMessage: '哈希类型长度不能超过{{ limit }}个字符')]
    private ?string $hashType = null;

    #[ORM\Column(type: Types::STRING, length: 1024, nullable: true, options: ['comment' => '哈希值'])]
    #[Assert\Length(max: 1024, maxMessage: '哈希值长度不能超过{{ limit }}个字符')]
    private ?string $hashValue = null;

    #[ORM\Column(type: Types::STRING, length: 2048, nullable: true, options: ['comment' => '下载地址'])]
    #[Assert\Url(message: '下载地址必须是有效的URL')]
    private ?string $downloadUrl = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: ProfitShareBillStatus::class, options: ['comment' => '状态'])]
    #[Assert\NotNull(message: '状态不能为空')]
    #[Assert\Choice(
        choices: ['pending', 'ready', 'downloading', 'downloaded', 'failed', 'expired'],
        message: '状态值不合法'
    )]
    private ProfitShareBillStatus $status = ProfitShareBillStatus::PENDING;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '下载时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class, message: '下载时间必须是DateTimeImmutable类型')]
    private ?\DateTimeImmutable $downloadedAt = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '本地存储路径'])]
    #[Assert\Length(max: 255, maxMessage: '本地存储路径长度不能超过{{ limit }}个字符')]
    private ?string $localPath = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '请求负载'])]
    #[Assert\Type(type: 'string', message: '请求负载必须是字符串类型')]
    private ?string $requestPayload = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '响应负载'])]
    #[Assert\Type(type: 'string', message: '响应负载必须是字符串类型')]
    private ?string $responsePayload = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '扩展数据'])]
    #[Assert\Type(type: 'array', message: '扩展数据必须是数组类型')]
    private ?array $metadata = null;

    public function __construct()
    {
        $this->billDate = new \DateTimeImmutable();
    }

    public function getMerchant(): ?Merchant
    {
        return $this->merchant;
    }

    public function setMerchant(?Merchant $merchant): void
    {
        $this->merchant = $merchant;
    }

    public function getSubMchId(): ?string
    {
        return $this->subMchId;
    }

    public function setSubMchId(?string $subMchId): void
    {
        $this->subMchId = $subMchId;
    }

    public function getBillDate(): \DateTimeImmutable
    {
        return $this->billDate;
    }

    public function setBillDate(\DateTimeImmutable $billDate): void
    {
        $this->billDate = $billDate;
    }

    public function getTarType(): ?string
    {
        return $this->tarType;
    }

    public function setTarType(?string $tarType): void
    {
        $this->tarType = $tarType;
    }

    public function getHashType(): ?string
    {
        return $this->hashType;
    }

    public function setHashType(?string $hashType): void
    {
        $this->hashType = $hashType;
    }

    public function getHashValue(): ?string
    {
        return $this->hashValue;
    }

    public function setHashValue(?string $hashValue): void
    {
        $this->hashValue = $hashValue;
    }

    public function getDownloadUrl(): ?string
    {
        return $this->downloadUrl;
    }

    public function setDownloadUrl(?string $downloadUrl): void
    {
        $this->downloadUrl = $downloadUrl;
    }

    public function getStatus(): ProfitShareBillStatus
    {
        return $this->status;
    }

    public function setStatus(ProfitShareBillStatus $status): void
    {
        $this->status = $status;
    }

    public function getDownloadedAt(): ?\DateTimeImmutable
    {
        return $this->downloadedAt;
    }

    public function setDownloadedAt(?\DateTimeImmutable $downloadedAt): void
    {
        $this->downloadedAt = $downloadedAt;
    }

    public function getLocalPath(): ?string
    {
        return $this->localPath;
    }

    public function setLocalPath(?string $localPath): void
    {
        $this->localPath = $localPath;
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

    public function __toString(): string
    {
        return sprintf(
            'ProfitShareBillTask(%s-%s-%s)',
            $this->subMchId ?? 'unknown',
            $this->billDate->format('Y-m-d'),
            $this->status->value
        );
    }
}
