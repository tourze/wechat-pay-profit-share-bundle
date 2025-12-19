<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReceiverAddRequest;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReceiverDeleteRequest;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareReceiverService;
use WechatPayBundle\Entity\Merchant;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareReceiverService::class)]
final class ProfitShareReceiverServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }

    public function testServiceExists(): void
    {
        $service = self::getService(ProfitShareReceiverService::class);
        $this->assertInstanceOf(ProfitShareReceiverService::class, $service);
    }

    /**
     * 测试 addReceiver 方法的基本功能和依赖注入
     */
    public function testAddReceiverMethod(): void
    {
        $service = self::getService(ProfitShareReceiverService::class);
        $merchant = new Merchant();
        $merchant->setMchId('1900000001');
        $merchant->setPemKey('fake-key');
        $merchant->setPemCert('fake-cert');
        $merchant->setCertSerial('ABC');

        $request = new ProfitShareReceiverAddRequest(
            subMchId: '1900000109',
            appid: 'wx8888888888888888',
            type: 'MERCHANT_ID',
            account: '1900000109',
            name: '测试商户',
            relationType: 'STORE',
        );

        // 验证方法签名和参数处理
        $this->assertTrue(method_exists($service, 'addReceiver'));
        $reflection = new \ReflectionMethod($service, 'addReceiver');
        $this->assertTrue($reflection->isPublic());
        $this->assertSame(2, $reflection->getNumberOfParameters());

        // 验证返回类型
        $returnType = $reflection->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('array', $returnType instanceof \ReflectionNamedType ? $returnType->getName() : (string) $returnType);

        // 验证请求对象
        $this->assertSame('1900000109', $request->getSubMchId());
        $this->assertSame('MERCHANT_ID', $request->getType());
        $this->assertSame('1900000109', $request->getAccount());
        $this->assertSame('测试商户', $request->getName());
        $this->assertSame('STORE', $request->getRelationType());

        // 验证载荷转换
        $payload = $request->toPayload();
        $this->assertIsArray($payload);
        $this->assertSame('1900000109', $payload['sub_mchid']);
        $this->assertSame('MERCHANT_ID', $payload['type']);
        $this->assertSame('1900000109', $payload['account']);
        $this->assertSame('测试商户', $payload['name']);
        $this->assertSame('STORE', $payload['relation_type']);
    }

    /**
     * 测试 deleteReceiver 方法的基本功能和依赖注入
     */
    public function testDeleteReceiverMethod(): void
    {
        $service = self::getService(ProfitShareReceiverService::class);
        $merchant = new Merchant();
        $merchant->setMchId('1900000001');
        $merchant->setPemKey('fake-key');
        $merchant->setPemCert('fake-cert');
        $merchant->setCertSerial('ABC');

        $request = new ProfitShareReceiverDeleteRequest(
            subMchId: '1900000109',
            appid: 'wx8888888888888888',
            type: 'MERCHANT_ID',
            account: '1900000109',
        );

        // 验证方法签名和参数处理
        $this->assertTrue(method_exists($service, 'deleteReceiver'));
        $reflection = new \ReflectionMethod($service, 'deleteReceiver');
        $this->assertTrue($reflection->isPublic());
        $this->assertSame(2, $reflection->getNumberOfParameters());

        // 验证返回类型
        $returnType = $reflection->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('array', $returnType instanceof \ReflectionNamedType ? $returnType->getName() : (string) $returnType);

        // 验证请求对象
        $this->assertSame('1900000109', $request->getSubMchId());
        $this->assertSame('MERCHANT_ID', $request->getType());
        $this->assertSame('1900000109', $request->getAccount());

        // 验证载荷转换
        $payload = $request->toPayload();
        $this->assertIsArray($payload);
        $this->assertSame('1900000109', $payload['sub_mchid']);
        $this->assertSame('MERCHANT_ID', $payload['type']);
        $this->assertSame('1900000109', $payload['account']);
    }

    /**
     * 测试添加接收方请求对象的基本功能
     */
    public function testProfitShareReceiverAddRequestObject(): void
    {
        $request = new ProfitShareReceiverAddRequest(
            subMchId: '1900000109',
            appid: 'wx8888888888888888',
            type: 'PERSONAL_OPENID',
            account: 'test-openid',
            name: '张三',
            relationType: 'DISTRIBUTOR',
        );

        $this->assertSame('1900000109', $request->getSubMchId());
        $this->assertSame('PERSONAL_OPENID', $request->getType());
        $this->assertSame('test-openid', $request->getAccount());
        $this->assertSame('张三', $request->getName());
        $this->assertSame('DISTRIBUTOR', $request->getRelationType());
    }

    /**
     * 测试删除接收方请求对象的基本功能
     */
    public function testProfitShareReceiverDeleteRequestObject(): void
    {
        $request = new ProfitShareReceiverDeleteRequest(
            subMchId: '1900000109',
            appid: 'wx8888888888888888',
            type: 'PERSONAL_OPENID',
            account: 'test-openid',
        );

        $this->assertSame('1900000109', $request->getSubMchId());
        $this->assertSame('PERSONAL_OPENID', $request->getType());
        $this->assertSame('test-openid', $request->getAccount());
    }
}
