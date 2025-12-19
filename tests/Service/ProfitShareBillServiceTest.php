<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareBillTask;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareBillStatus;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareBillDownloadRequest;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareBillRequest;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareBillService;
use WechatPayBundle\Entity\Merchant;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareBillService::class)]
final class ProfitShareBillServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }

    public function testServiceExists(): void
    {
        $service = self::getService(ProfitShareBillService::class);
        $this->assertInstanceOf(ProfitShareBillService::class, $service);
    }

    /**
     * 测试 applyBill 方法的基本功能和依赖注入
     */
    public function testApplyBillMethod(): void
    {
        $service = self::getService(ProfitShareBillService::class);
        $merchant = new Merchant();
        $merchant->setMchId('1900000001');
        $merchant->setPemKey('fake-key');
        $merchant->setPemCert('fake-cert');
        $merchant->setCertSerial('ABC');

        $billDate = new \DateTime('2024-01-01');
        $request = new ProfitShareBillRequest(
            subMchId: '1900000109',
            billDate: $billDate,
            tarType: 'SMALL',
        );

        // 验证方法签名和参数处理
        $this->assertTrue(method_exists($service, 'applyBill'));
        $reflection = new \ReflectionMethod($service, 'applyBill');
        $this->assertTrue($reflection->isPublic());
        $this->assertSame(2, $reflection->getNumberOfParameters());

        // 验证请求对象
        $this->assertSame('1900000109', $request->getSubMchId());
        $this->assertSame($billDate, $request->getBillDate());
        $this->assertSame('SMALL', $request->getTarType());

        // 验证查询参数转换
        $query = $request->toQuery();
        $this->assertIsArray($query);
        $this->assertSame('1900000109', $query['sub_mchid']);
        $this->assertSame('2024-01-01', $query['bill_date']);
        $this->assertSame('SMALL', $query['tar_type']);
    }

    /**
     * 测试 downloadBill 方法的基本功能和依赖注入
     */
    public function testDownloadBillMethod(): void
    {
        $service = self::getService(ProfitShareBillService::class);
        $merchant = new Merchant();
        $merchant->setMchId('1900000001');
        $merchant->setPemKey('fake-key');
        $merchant->setPemCert('fake-cert');
        $merchant->setCertSerial('ABC');

        // 创建模拟的账单任务
        $task = new ProfitShareBillTask();
        $task->setMerchant($merchant);
        $task->setSubMchId('1900000109');
        $task->setBillDate(new \DateTimeImmutable('2024-01-01'));
        $task->setTarType('SMALL');
        $task->setDownloadUrl('https://api.mch.weixin.qq.com/v3/profitsharing/bills/download');
        $task->setStatus(ProfitShareBillStatus::READY);

        $downloadRequest = new ProfitShareBillDownloadRequest(
            localPath: '/tmp/bill.csv',
            tarType: 'SMALL',
        );

        // 验证方法签名和参数处理
        $this->assertTrue(method_exists($service, 'downloadBill'));
        $reflection = new \ReflectionMethod($service, 'downloadBill');
        $this->assertTrue($reflection->isPublic());
        $this->assertSame(3, $reflection->getNumberOfParameters());

        // 验证下载请求对象
        $this->assertSame('/tmp/bill.csv', $downloadRequest->getLocalPath());
        $this->assertSame('SMALL', $downloadRequest->getTarType());
    }

    /**
     * 测试账单任务对象的基本功能
     */
    public function testProfitShareBillTaskObject(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('1900000001');

        $task = new ProfitShareBillTask();
        $task->setMerchant($merchant);
        $task->setSubMchId('1900000109');
        $task->setBillDate(new \DateTimeImmutable('2024-01-01'));
        $task->setTarType('SMALL');
        $task->setStatus(ProfitShareBillStatus::READY);

        $this->assertSame($merchant, $task->getMerchant());
        $this->assertSame('1900000109', $task->getSubMchId());
        $this->assertSame('SMALL', $task->getTarType());
        $this->assertSame(ProfitShareBillStatus::READY, $task->getStatus());
    }

    /**
     * 测试账单下载请求对象的基本功能
     */
    public function testProfitShareBillDownloadRequestObject(): void
    {
        $request = new ProfitShareBillDownloadRequest(
            localPath: '/tmp/test.csv',
            tarType: 'GZIP',
            downloadUrl: 'https://example.com/download',
            expectedHashType: 'SHA1',
            expectedHashValue: 'abc123',
        );

        $this->assertSame('/tmp/test.csv', $request->getLocalPath());
        $this->assertSame('GZIP', $request->getTarType());
        $this->assertSame('https://example.com/download', $request->getDownloadUrl());
        $this->assertSame('SHA1', $request->getExpectedHashType());
        $this->assertSame('abc123', $request->getExpectedHashValue());
    }
}
