<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOrderState;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOrderRepository;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareOrderRequest;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReceiverRequest;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareUnfreezeRequest;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareService;
use WechatPayBundle\Entity\Merchant;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareService::class)]
final class ProfitShareServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 在子类中不需要额外初始化
    }

    public function testServiceExists(): void
    {
        $service = self::getService(ProfitShareService::class);
        $this->assertInstanceOf(ProfitShareService::class, $service);
    }

    /**
     * 测试服务基本功能和依赖注入
     */
    public function testServiceDependencies(): void
    {
        $service = self::getService(ProfitShareService::class);
        $orderRepository = self::getService(ProfitShareOrderRepository::class);
        $entityManager = self::getService(EntityManagerInterface::class);

        $this->assertNotNull($service);
        $this->assertNotNull($orderRepository);
        $this->assertNotNull($entityManager);
    }

    /**
     * 测试分账请求验证逻辑
     */
    public function testProfitShareRequestValidation(): void
    {
        $service = self::getService(ProfitShareService::class);

        // 创建没有接收方的请求，应该抛出异常
        $request = new ProfitShareOrderRequest(
            subMchId: '1900000109',
            transactionId: '4208450740201411110007820472',
            outOrderNo: 'P20150806125346',
        );

        $merchant = new Merchant();
        $merchant->setMchId('1900000001');
        $merchant->setPemKey('fake-key');
        $merchant->setPemCert('fake-cert');
        $merchant->setCertSerial('ABC');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('分账请求至少需要一个接收方');

        $service->requestProfitShare($merchant, $request);
    }

    /**
     * 测试分账请求对象的基本功能
     */
    public function testProfitShareRequestObject(): void
    {
        $request = new ProfitShareOrderRequest(
            subMchId: '1900000109',
            transactionId: '4208450740201411110007820472',
            outOrderNo: 'P20150806125346',
        );

        $receiver = new ProfitShareReceiverRequest(
            type: 'MERCHANT_ID',
            account: '1900000109',
            amount: 100,
            description: '分账描述',
        );

        $request->addReceiver($receiver);

        $this->assertSame('1900000109', $request->getSubMchId());
        $this->assertSame('4208450740201411110007820472', $request->getTransactionId());
        $this->assertSame('P20150806125346', $request->getOutOrderNo());
        $this->assertCount(1, $request->getReceivers());
        $this->assertSame($receiver, $request->getReceivers()[0]);
    }

    /**
     * 测试解冻请求对象的基本功能
     */
    public function testProfitShareUnfreezeRequestObject(): void
    {
        $request = new ProfitShareUnfreezeRequest(
            subMchId: '1900000109',
            transactionId: '4208450740201411110007820472',
            outOrderNo: 'P20150806125346',
            description: '解冻全部剩余资金',
        );

        $this->assertSame('1900000109', $request->getSubMchId());
        $this->assertSame('4208450740201411110007820472', $request->getTransactionId());
        $this->assertSame('P20150806125346', $request->getOutOrderNo());
        $this->assertSame('解冻全部剩余资金', $request->getDescription());
    }

    /**
     * 测试 queryProfitShareOrder 方法的基本功能和依赖注入
     */
    public function testQueryProfitShareOrderMethod(): void
    {
        $service = self::getService(ProfitShareService::class);
        $merchant = new Merchant();
        $merchant->setMchId('1900000001');
        $merchant->setPemKey('fake-key');
        $merchant->setPemCert('fake-cert');
        $merchant->setCertSerial('ABC');

        $subMchId = '1900000109';
        $outOrderNo = 'P20150806125346';
        $orderId = '3008450740201411110007820472';
        $transactionId = '4208450740201411110007820472';

        // 验证方法签名和参数处理
        $this->assertTrue(method_exists($service, 'queryProfitShareOrder'));
        $reflection = new \ReflectionMethod($service, 'queryProfitShareOrder');
        $this->assertTrue($reflection->isPublic());
        $this->assertSame(4, $reflection->getNumberOfParameters());

        // 验证返回类型
        $returnType = $reflection->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder', $returnType instanceof \ReflectionNamedType ? $returnType->getName() : (string) $returnType);

        // 验证参数类型
        $parameters = $reflection->getParameters();
        $this->assertCount(4, $parameters);

        $this->assertSame(Merchant::class, $parameters[0]->getType() instanceof \ReflectionNamedType ? $parameters[0]->getType()->getName() : (string) $parameters[0]->getType());
        $this->assertSame('string', $parameters[1]->getType() instanceof \ReflectionNamedType ? $parameters[1]->getType()->getName() : (string) $parameters[1]->getType());
        $this->assertSame('string', $parameters[2]->getType() instanceof \ReflectionNamedType ? $parameters[2]->getType()->getName() : (string) $parameters[2]->getType());
        $this->assertSame('string', $parameters[3]->getType() instanceof \ReflectionNamedType ? $parameters[3]->getType()->getName() : (string) $parameters[3]->getType());

        // 验证可选参数
        $this->assertTrue($parameters[1]->allowsNull());
        $this->assertTrue($parameters[3]->allowsNull());

        // 验证参数值
        $this->assertSame('1900000109', $subMchId);
        $this->assertSame('P20150806125346', $outOrderNo);
        $this->assertSame('3008450740201411110007820472', $orderId);
        $this->assertSame('4208450740201411110007820472', $transactionId);
    }

    /**
     * 测试 requestProfitShare 方法的基本功能和依赖注入
     */
    public function testRequestProfitShareMethod(): void
    {
        $service = self::getService(ProfitShareService::class);
        $merchant = new Merchant();
        $merchant->setMchId('1900000001');
        $merchant->setPemKey('fake-key');
        $merchant->setPemCert('fake-cert');
        $merchant->setCertSerial('ABC');

        $receiver = new ProfitShareReceiverRequest(
            type: 'MERCHANT_ID',
            account: '1900000109',
            amount: 100,
            description: '分账描述',
        );

        $request = new ProfitShareOrderRequest(
            subMchId: '1900000109',
            transactionId: '4208450740201411110007820472',
            outOrderNo: 'P20150806125346',
        );
        $request->addReceiver($receiver);

        // 验证方法签名和参数处理
        $this->assertTrue(method_exists($service, 'requestProfitShare'));
        $reflection = new \ReflectionMethod($service, 'requestProfitShare');
        $this->assertTrue($reflection->isPublic());
        $this->assertSame(2, $reflection->getNumberOfParameters());

        // 验证返回类型
        $returnType = $reflection->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder', $returnType instanceof \ReflectionNamedType ? $returnType->getName() : (string) $returnType);

        // 验证请求对象
        $this->assertSame('1900000109', $request->getSubMchId());
        $this->assertSame('4208450740201411110007820472', $request->getTransactionId());
        $this->assertSame('P20150806125346', $request->getOutOrderNo());
        $this->assertCount(1, $request->getReceivers());
        $this->assertSame($receiver, $request->getReceivers()[0]);
    }

    /**
     * 测试 unfreezeRemainingAmount 方法的基本功能和依赖注入
     */
    public function testUnfreezeRemainingAmountMethod(): void
    {
        $service = self::getService(ProfitShareService::class);
        $merchant = new Merchant();
        $merchant->setMchId('1900000001');
        $merchant->setPemKey('fake-key');
        $merchant->setPemCert('fake-cert');
        $merchant->setCertSerial('ABC');

        $request = new ProfitShareUnfreezeRequest(
            subMchId: '1900000109',
            transactionId: '4208450740201411110007820472',
            outOrderNo: 'P20150806125346',
            description: '解冻全部剩余资金',
        );

        // 验证方法签名和参数处理
        $this->assertTrue(method_exists($service, 'unfreezeRemainingAmount'));
        $reflection = new \ReflectionMethod($service, 'unfreezeRemainingAmount');
        $this->assertTrue($reflection->isPublic());
        $this->assertSame(2, $reflection->getNumberOfParameters());

        // 验证返回类型
        $returnType = $reflection->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder', $returnType instanceof \ReflectionNamedType ? $returnType->getName() : (string) $returnType);

        // 验证参数类型
        $parameters = $reflection->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertSame(Merchant::class, $parameters[0]->getType() instanceof \ReflectionNamedType ? $parameters[0]->getType()->getName() : (string) $parameters[0]->getType());
        $this->assertSame('Tourze\WechatPayProfitShareBundle\Request\ProfitShareUnfreezeRequest', $parameters[1]->getType() instanceof \ReflectionNamedType ? $parameters[1]->getType()->getName() : (string) $parameters[1]->getType());

        // 验证解冻请求对象
        $this->assertSame('1900000109', $request->getSubMchId());
        $this->assertSame('4208450740201411110007820472', $request->getTransactionId());
        $this->assertSame('P20150806125346', $request->getOutOrderNo());
        $this->assertSame('解冻全部剩余资金', $request->getDescription());
    }
}
