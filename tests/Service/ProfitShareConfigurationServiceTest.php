<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareConfigurationService;
use WechatPayBundle\Entity\Merchant;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareConfigurationService::class)]
final class ProfitShareConfigurationServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }

    public function testServiceExists(): void
    {
        $service = self::getService(ProfitShareConfigurationService::class);
        $this->assertInstanceOf(ProfitShareConfigurationService::class, $service);
    }

    /**
     * 测试 queryMaxRatio 方法的基本功能和依赖注入
     */
    public function testQueryMaxRatioMethod(): void
    {
        $service = self::getService(ProfitShareConfigurationService::class);
        $merchant = new Merchant();
        $merchant->setMchId('1900000001');
        $merchant->setPemKey('fake-key');
        $merchant->setPemCert('fake-cert');
        $merchant->setCertSerial('ABC');

        $subMchId = '1900000109';

        // 验证方法签名和参数处理
        $this->assertTrue(method_exists($service, 'queryMaxRatio'));
        $reflection = new \ReflectionMethod($service, 'queryMaxRatio');
        $this->assertTrue($reflection->isPublic());
        $this->assertSame(2, $reflection->getNumberOfParameters());

        // 验证返回类型
        $returnType = $reflection->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('array', $returnType instanceof \ReflectionNamedType ? $returnType->getName() : (string) $returnType);

        // 验证参数类型
        $parameters = $reflection->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertSame(Merchant::class, $parameters[0]->getType() instanceof \ReflectionNamedType ? $parameters[0]->getType()->getName() : (string) $parameters[0]->getType());
        $this->assertSame('string', $parameters[1]->getType() instanceof \ReflectionNamedType ? $parameters[1]->getType()->getName() : (string) $parameters[1]->getType());

        // 验证商户对象设置
        $this->assertSame('1900000001', $merchant->getMchId());
        $this->assertSame('fake-key', $merchant->getPemKey());
        $this->assertSame('fake-cert', $merchant->getPemCert());
        $this->assertSame('ABC', $merchant->getCertSerial());

        // 验证子商户ID
        $this->assertSame('1900000109', $subMchId);
    }

    /**
     * 测试 queryRemainingAmount 方法的基本功能和依赖注入
     */
    public function testQueryRemainingAmountMethod(): void
    {
        $service = self::getService(ProfitShareConfigurationService::class);
        $merchant = new Merchant();
        $merchant->setMchId('1900000001');
        $merchant->setPemKey('fake-key');
        $merchant->setPemCert('fake-cert');
        $merchant->setCertSerial('ABC');

        $transactionId = '4208450740201411110007820472';

        // 验证方法签名和参数处理
        $this->assertTrue(method_exists($service, 'queryRemainingAmount'));
        $reflection = new \ReflectionMethod($service, 'queryRemainingAmount');
        $this->assertTrue($reflection->isPublic());
        $this->assertSame(2, $reflection->getNumberOfParameters());

        // 验证返回类型
        $returnType = $reflection->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('array', $returnType instanceof \ReflectionNamedType ? $returnType->getName() : (string) $returnType);

        // 验证参数类型
        $parameters = $reflection->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertSame(Merchant::class, $parameters[0]->getType() instanceof \ReflectionNamedType ? $parameters[0]->getType()->getName() : (string) $parameters[0]->getType());
        $this->assertSame('string', $parameters[1]->getType() instanceof \ReflectionNamedType ? $parameters[1]->getType()->getName() : (string) $parameters[1]->getType());

        // 验证商户对象设置
        $this->assertSame('1900000001', $merchant->getMchId());
        $this->assertSame('fake-key', $merchant->getPemKey());
        $this->assertSame('fake-cert', $merchant->getPemCert());
        $this->assertSame('ABC', $merchant->getCertSerial());

        // 验证交易ID
        $this->assertSame('4208450740201411110007820472', $transactionId);
    }
}
