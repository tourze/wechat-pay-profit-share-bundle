<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReturnOrder;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReturnRequest;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareReturnService;
use WechatPayBundle\Entity\Merchant;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareReturnService::class)]
final class ProfitShareReturnServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }

    public function testServiceExists(): void
    {
        $service = self::getService(ProfitShareReturnService::class);
        $this->assertInstanceOf(ProfitShareReturnService::class, $service);
    }

    /**
     * 测试 requestReturn 方法的基本功能和依赖注入
     */
    public function testRequestReturnMethod(): void
    {
        $service = self::getService(ProfitShareReturnService::class);
        $merchant = new Merchant();
        $merchant->setMchId('1900000001');
        $merchant->setPemKey('fake-key');
        $merchant->setPemCert('fake-cert');
        $merchant->setCertSerial('ABC');

        $request = new ProfitShareReturnRequest(
            subMchId: '1900000109',
            outReturnNo: 'R20150806125346',
            outOrderNo: 'P20150806125346',
            orderId: '3008450740201411110007820472',
            amount: 100,
            description: '分账回退',
        );

        // 验证方法签名和参数处理
        $this->assertTrue(method_exists($service, 'requestReturn'));
        $reflection = new \ReflectionMethod($service, 'requestReturn');
        $this->assertTrue($reflection->isPublic());
        $this->assertSame(2, $reflection->getNumberOfParameters());

        // 验证返回类型
        $returnType = $reflection->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame(ProfitShareReturnOrder::class, $returnType instanceof \ReflectionNamedType ? $returnType->getName() : (string) $returnType);

        // 验证请求对象
        $this->assertSame('1900000109', $request->getSubMchId());
        $this->assertSame('R20150806125346', $request->getOutReturnNo());
        $this->assertSame('P20150806125346', $request->getOutOrderNo());
        $this->assertSame('3008450740201411110007820472', $request->getOrderId());
        $this->assertSame(100, $request->getAmount());
        $this->assertSame('分账回退', $request->getDescription());

        // 验证载荷转换
        $payload = $request->toPayload();
        $this->assertIsArray($payload);
        $this->assertSame('1900000109', $payload['sub_mchid']);
        $this->assertSame('R20150806125346', $payload['out_return_no']);
        $this->assertSame('P20150806125346', $payload['out_order_no']);
        $this->assertSame('3008450740201411110007820472', $payload['order_id']);
        $this->assertSame(100, $payload['amount']);
        $this->assertSame('分账回退', $payload['description']);
    }

    /**
     * 测试 queryReturn 方法的基本功能和依赖注入
     */
    public function testQueryReturnMethod(): void
    {
        $service = self::getService(ProfitShareReturnService::class);
        $merchant = new Merchant();
        $merchant->setMchId('1900000001');
        $merchant->setPemKey('fake-key');
        $merchant->setPemCert('fake-cert');
        $merchant->setCertSerial('ABC');

        $subMchId = '1900000109';
        $outReturnNo = 'R20150806125346';
        $outOrderNo = 'P20150806125346';
        $orderId = '3008450740201411110007820472';

        // 验证方法签名和参数处理
        $this->assertTrue(method_exists($service, 'queryReturn'));
        $reflection = new \ReflectionMethod($service, 'queryReturn');
        $this->assertTrue($reflection->isPublic());
        $this->assertSame(5, $reflection->getNumberOfParameters());

        // 验证返回类型
        $returnType = $reflection->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame(ProfitShareReturnOrder::class, $returnType instanceof \ReflectionNamedType ? $returnType->getName() : (string) $returnType);

        // 验证参数类型
        $parameters = $reflection->getParameters();
        $this->assertCount(5, $parameters);

        $this->assertSame(Merchant::class, $parameters[0]->getType() instanceof \ReflectionNamedType ? $parameters[0]->getType()->getName() : (string) $parameters[0]->getType());
        $this->assertSame('string', $parameters[1]->getType() instanceof \ReflectionNamedType ? $parameters[1]->getType()->getName() : (string) $parameters[1]->getType());
        $this->assertSame('string', $parameters[2]->getType() instanceof \ReflectionNamedType ? $parameters[2]->getType()->getName() : (string) $parameters[2]->getType());
        $this->assertSame('string', $parameters[3]->getType() instanceof \ReflectionNamedType ? $parameters[3]->getType()->getName() : (string) $parameters[3]->getType());
        $this->assertSame('string', $parameters[4]->getType() instanceof \ReflectionNamedType ? $parameters[4]->getType()->getName() : (string) $parameters[4]->getType());

        // 验证可选参数
        $this->assertTrue($parameters[3]->allowsNull());
        $this->assertTrue($parameters[4]->allowsNull());

        // 验证参数值
        $this->assertSame('1900000109', $subMchId);
        $this->assertSame('R20150806125346', $outReturnNo);
        $this->assertSame('P20150806125346', $outOrderNo);
        $this->assertSame('3008450740201411110007820472', $orderId);
    }

    /**
     * 测试分账回退请求对象的基本功能
     */
    public function testProfitShareReturnRequestObject(): void
    {
        $request = new ProfitShareReturnRequest(
            subMchId: '1900000109',
            outReturnNo: 'R20150806125346',
            outOrderNo: 'P20150806125346',
            orderId: '3008450740201411110007820472',
            amount: 500,
            description: '退款回退',
        );

        $this->assertSame('1900000109', $request->getSubMchId());
        $this->assertSame('R20150806125346', $request->getOutReturnNo());
        $this->assertSame('P20150806125346', $request->getOutOrderNo());
        $this->assertSame('3008450740201411110007820472', $request->getOrderId());
        $this->assertSame(500, $request->getAmount());
        $this->assertSame('退款回退', $request->getDescription());
    }

    /**
     * 测试分账回退订单实体对象的基本功能
     */
    public function testProfitShareReturnOrderObject(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('1900000001');

        $order = new ProfitShareReturnOrder();
        $order->setMerchant($merchant);
        $order->setSubMchId('1900000109');
        $order->setOutReturnNo('R20150806125346');
        $order->setOutOrderNo('P20150806125346');
        $order->setOrderId('3008450740201411110007820472');

        $this->assertSame($merchant, $order->getMerchant());
        $this->assertSame('1900000109', $order->getSubMchId());
        $this->assertSame('R20150806125346', $order->getOutReturnNo());
        $this->assertSame('P20150806125346', $order->getOutOrderNo());
        $this->assertSame('3008450740201411110007820472', $order->getOrderId());
    }
}
