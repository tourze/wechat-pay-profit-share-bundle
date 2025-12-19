<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareBillDownloadRequest;

/**
 * @internal
 */
#[CoversClass(ProfitShareBillDownloadRequest::class)]
final class ProfitShareBillDownloadRequestTest extends TestCase
{
    public function testCreateRequestWithDefaultValues(): void
    {
        $request = new ProfitShareBillDownloadRequest();

        $this->assertNull($request->getDownloadUrl());
        $this->assertNull($request->getLocalPath());
        $this->assertNull($request->getExpectedHashType());
        $this->assertNull($request->getExpectedHashValue());
        $this->assertNull($request->getTarType());
    }

    public function testCreateRequestWithValues(): void
    {
        $request = new ProfitShareBillDownloadRequest(
            downloadUrl: 'https://example.com/bill.tar.gz',
            localPath: '/tmp/bill.tar.gz',
            expectedHashType: 'SHA256',
            expectedHashValue: 'abc123def456',
            tarType: 'gzip'
        );

        $this->assertSame('https://example.com/bill.tar.gz', $request->getDownloadUrl());
        $this->assertSame('/tmp/bill.tar.gz', $request->getLocalPath());
        $this->assertSame('SHA256', $request->getExpectedHashType());
        $this->assertSame('abc123def456', $request->getExpectedHashValue());
        $this->assertSame('gzip', $request->getTarType());
    }

    public function testSetDownloadUrl(): void
    {
        $request = new ProfitShareBillDownloadRequest();
        $request->setDownloadUrl('https://new-example.com/bill.tar.gz');

        $this->assertSame('https://new-example.com/bill.tar.gz', $request->getDownloadUrl());
    }

    public function testSetLocalPath(): void
    {
        $request = new ProfitShareBillDownloadRequest();
        $request->setLocalPath('/new/path/bill.tar.gz');

        $this->assertSame('/new/path/bill.tar.gz', $request->getLocalPath());
    }

    public function testSetExpectedHashType(): void
    {
        $request = new ProfitShareBillDownloadRequest();
        $request->setExpectedHashType('MD5');

        $this->assertSame('MD5', $request->getExpectedHashType());
    }

    public function testSetExpectedHashValue(): void
    {
        $request = new ProfitShareBillDownloadRequest();
        $request->setExpectedHashValue('newhash123456');

        $this->assertSame('newhash123456', $request->getExpectedHashValue());
    }

    public function testSetTarType(): void
    {
        $request = new ProfitShareBillDownloadRequest();
        $request->setTarType('bzip2');

        $this->assertSame('bzip2', $request->getTarType());
    }

    public function testSetNullValues(): void
    {
        $request = new ProfitShareBillDownloadRequest(
            downloadUrl: 'https://example.com/bill.tar.gz',
            localPath: '/tmp/bill.tar.gz',
            expectedHashType: 'SHA256',
            expectedHashValue: 'abc123def456',
            tarType: 'gzip'
        );

        // Set all values to null
        $request->setDownloadUrl(null);
        $request->setLocalPath(null);
        $request->setExpectedHashType(null);
        $request->setExpectedHashValue(null);
        $request->setTarType(null);

        $this->assertNull($request->getDownloadUrl());
        $this->assertNull($request->getLocalPath());
        $this->assertNull($request->getExpectedHashType());
        $this->assertNull($request->getExpectedHashValue());
        $this->assertNull($request->getTarType());
    }
}
