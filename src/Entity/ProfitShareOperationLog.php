<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOperationType;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOperationLogRepository;
use WechatPayBundle\Entity\Merchant;

/**
 * @see https://pay.weixin.qq.com/doc/v3/partner/4012466854
 */

#[ORM\Entity(repositoryClass: ProfitShareOperationLogRepository::class)]
#[ORM\Table(name: 'wechat_pay_profit_share_operation_log', options: ['comment' => '微信支付-分账操作日志'])]
class ProfitShareOperationLog implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use BlameableAware;

    #[ORM\ManyToOne(targetEntity: Merchant::class)]
    private ?Merchant $merchant = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '特约商户号'])]
    #[Assert\Length(min: 1, max: 32, minMessage: '特约商户号长度不能少于{{ limit }}个字符', maxMessage: '特约商户号长度不能超过{{ limit }}个字符')]
    private ?string $subMchId = null;

    #[ORM\Column(type: Types::STRING, length: 40, enumType: ProfitShareOperationType::class, options: ['comment' => '操作类型'])]
    #[Assert\NotNull(message: '操作类型不能为空')]
    #[Assert\Choice(callback: [ProfitShareOperationType::class, 'cases'], message: '操作类型值不合法')]
    private ProfitShareOperationType $type = ProfitShareOperationType::REQUEST_ORDER;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否成功'])]
    #[Assert\Type(type: 'bool', message: '成功状态必须是布尔类型')]
    private bool $success = true;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '错误码'])]
    #[Assert\Length(max: 32, maxMessage: '错误码长度不能超过{{ limit }}个字符')]
    private ?string $errorCode = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '错误信息'])]
    #[Assert\Length(max: 255, maxMessage: '错误信息长度不能超过{{ limit }}个字符')]
    private ?string $errorMessage = null;

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

    public function getType(): ProfitShareOperationType
    {
        return $this->type;
    }

    public function setType(ProfitShareOperationType $type): void
    {
        $this->type = $type;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * 兼容方法 - 转调 isSuccess()
     * @deprecated 使用 isSuccess() 代替
     */
    public function getSuccess(): bool
    {
        return $this->isSuccess();
    }

    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function setErrorCode(?string $errorCode): void
    {
        $this->errorCode = $errorCode;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
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
            'ProfitShareOperationLog(%s-%s-%s)',
            $this->type->value,
            $this->success ? 'success' : 'failed',
            $this->errorCode ?? 'ok'
        );
    }
}
