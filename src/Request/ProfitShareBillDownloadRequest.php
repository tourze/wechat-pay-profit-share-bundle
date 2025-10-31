<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Request;

class ProfitShareBillDownloadRequest
{
    public function __construct(
        private ?string $downloadUrl = null,
        private ?string $localPath = null,
        private ?string $expectedHashType = null,
        private ?string $expectedHashValue = null,
        private ?string $tarType = null,
    ) {
    }

    public function getDownloadUrl(): ?string
    {
        return $this->downloadUrl;
    }

    public function setDownloadUrl(?string $downloadUrl): void
    {
        $this->downloadUrl = $downloadUrl;
    }

    public function getLocalPath(): ?string
    {
        return $this->localPath;
    }

    public function setLocalPath(?string $localPath): void
    {
        $this->localPath = $localPath;
    }

    public function getExpectedHashType(): ?string
    {
        return $this->expectedHashType;
    }

    public function setExpectedHashType(?string $expectedHashType): void
    {
        $this->expectedHashType = $expectedHashType;
    }

    public function getExpectedHashValue(): ?string
    {
        return $this->expectedHashValue;
    }

    public function setExpectedHashValue(?string $expectedHashValue): void
    {
        $this->expectedHashValue = $expectedHashValue;
    }

    public function getTarType(): ?string
    {
        return $this->tarType;
    }

    public function setTarType(?string $tarType): void
    {
        $this->tarType = $tarType;
    }
}
