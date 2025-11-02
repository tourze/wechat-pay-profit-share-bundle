<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Service;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOperationLogRepository;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReceiverAddRequest;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReceiverDeleteRequest;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareReceiverService;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareReceiverService::class)]
class ProfitShareReceiverServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }

    public function testAddReceiverSendsEncryptedName(): void
    {
        // 创建Mock依赖
        $operationRepository = $this->createMock(ProfitShareOperationLogRepository::class);
        $operationRepository->expects($this->once())->method('save');

        $builder = new FakeBuilderChainable([
            new Response(200, [], Json::encode([
                'sub_mchid' => '1900000109',
                'type' => 'MERCHANT_ID',
                'account' => '1900000109',
            ])),
        ]);

        $keys = $this->generateKeyPair();

        $builderFactory = $this->createMock(WechatPayBuilder::class);
        $builderFactory->expects($this->once())->method('genBuilder')->willReturn($builder);
        $builderFactory->expects($this->once())
            ->method('getPlatformPublicKey')
            ->willReturn($keys['public'])
        ;
        $builderFactory->expects($this->once())
            ->method('getPlatformCertificateSerial')
            ->willReturn('SERIAL123')
        ;

        // 将Mock依赖注入到容器中
        self::getContainer()->set(ProfitShareOperationLogRepository::class, $operationRepository);
        self::getContainer()->set(WechatPayBuilder::class, $builderFactory);

        // 从容器获取服务
        $service = self::getService(ProfitShareReceiverService::class);

        $merchant = new Merchant();
        $merchant->setMchId('1900000001');

        $request = new ProfitShareReceiverAddRequest(
            subMchId: '1900000109',
            appid: 'wx123456',
            type: 'MERCHANT_ID',
            account: '1900000109',
            relationType: 'SERVICE_PROVIDER',
            name: '测试商户',
        );

        $service->addReceiver($merchant, $request);

        $this->assertSame('v3/profitsharing/receivers/add', $builder->lastSegment);
        $this->assertIsArray($builder->lastOptions['headers'] ?? []);
        /** @var array<string, mixed> $headers */
        $headers = $builder->lastOptions['headers'] ?? [];
        $this->assertSame('SERIAL123', $headers['Wechatpay-Serial'] ?? null);
        /** @var array<string, mixed> $json */
        $json = $builder->lastOptions['json'] ?? [];
        $this->assertIsArray($json);
        $this->assertArrayHasKey('name', $json);
        $this->assertNotSame('测试商户', $json['name']);
    }

    /**
     * @return array{public: mixed, private: mixed}
     */
    private function generateKeyPair(): array
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if (false === $resource) {
            throw new \RuntimeException('Failed to generate key');
        }

        $details = openssl_pkey_get_details($resource);
        if (false === $details) {
            throw new \RuntimeException('Failed to get key details');
        }
        /** @var array<string, mixed> $details */
        $publicKey = $details['key'];

        return [
            'public' => $publicKey,
            'private' => $resource,
        ];
    }

    public function testDeleteReceiver(): void
    {
        // 创建Mock依赖
        $operationRepository = $this->createMock(ProfitShareOperationLogRepository::class);
        $operationRepository->expects($this->once())->method('save');

        $builder = new FakeBuilderChainable([
            new Response(200, [], Json::encode([
                'result' => 'SUCCESS',
            ])),
        ]);

        $builderFactory = $this->createMock(WechatPayBuilder::class);
        $builderFactory->expects($this->once())->method('genBuilder')->willReturn($builder);
        // 将Mock依赖注入到容器中
        self::getContainer()->set(ProfitShareOperationLogRepository::class, $operationRepository);
        self::getContainer()->set(WechatPayBuilder::class, $builderFactory);

        // 从容器获取服务
        $service = self::getService(ProfitShareReceiverService::class);

        $merchant = new Merchant();
        $merchant->setMchId('1900000001');

        $request = new ProfitShareReceiverDeleteRequest(
            subMchId: '1900000109',
            appid: 'wx123456',
            type: 'MERCHANT_ID',
            account: '1900000109',
        );

        $service->deleteReceiver($merchant, $request);

        $this->assertSame('v3/profitsharing/receivers/delete', $builder->lastSegment);
    }
}
