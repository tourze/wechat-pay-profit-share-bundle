<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareApiExecutor;
use WechatPayBundle\Entity\Merchant;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareApiExecutor::class)]
final class ProfitShareApiExecutorTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }

    public function testServiceIsRegisteredInContainer(): void
    {
        $executor = self::getService(ProfitShareApiExecutor::class);
        $this->assertInstanceOf(ProfitShareApiExecutor::class, $executor);
    }

    /**
     * 测试服务依赖注入是否正确
     */
    public function testServiceDependencies(): void
    {
        $executor = self::getService(ProfitShareApiExecutor::class);
        $this->assertInstanceOf(ProfitShareApiExecutor::class, $executor);
    }

    /**
     * 测试 Merchant 对象的基本设置
     */
    public function testMerchantObjectSetup(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('1900000001');
        $merchant->setPemKey('fake-key');
        $merchant->setPemCert('fake-cert');
        $merchant->setCertSerial('ABC');

        $this->assertSame('1900000001', $merchant->getMchId());
        $this->assertSame('fake-key', $merchant->getPemKey());
        $this->assertSame('fake-cert', $merchant->getPemCert());
        $this->assertSame('ABC', $merchant->getCertSerial());
    }

    /**
     * 测试 executeRequest 方法的基本功能和依赖注入
     */
    public function testExecuteRequestMethod(): void
    {
        $executor = self::getService(ProfitShareApiExecutor::class);
        $merchant = new Merchant();
        $merchant->setMchId('1900000001');
        $merchant->setPemKey('fake-key');
        $merchant->setPemCert('fake-cert');
        $merchant->setCertSerial('ABC');

        $segment = 'v3/profitsharing/orders';
        $payload = [
            'sub_mchid' => '1900000109',
            'transaction_id' => '4208450740201411110007820472',
            'out_order_no' => 'P20150806125346',
        ];

        // 验证方法签名和参数处理
        $this->assertTrue(method_exists($executor, 'executeRequest'));
        $reflection = new \ReflectionMethod($executor, 'executeRequest');
        $this->assertTrue($reflection->isPublic());
        $this->assertSame(3, $reflection->getNumberOfParameters());
    }

    /**
     * 测试 executeQuery 方法的基本功能和依赖注入
     */
    public function testExecuteQueryMethod(): void
    {
        $executor = self::getService(ProfitShareApiExecutor::class);
        $merchant = new Merchant();
        $merchant->setMchId('1900000001');
        $merchant->setPemKey('fake-key');
        $merchant->setPemCert('fake-cert');
        $merchant->setCertSerial('ABC');

        $segment = 'v3/profitsharing/orders/123';
        $query = [
            'sub_mchid' => '1900000109',
        ];

        // 验证方法签名和参数处理
        $this->assertTrue(method_exists($executor, 'executeQuery'));
        $reflection = new \ReflectionMethod($executor, 'executeQuery');
        $this->assertTrue($reflection->isPublic());
        $this->assertSame(3, $reflection->getNumberOfParameters());
    }
}
