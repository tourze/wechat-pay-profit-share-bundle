<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Service;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOrderState;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOperationLogRepository;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOrderRepository;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareOrderRequest;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReceiverRequest;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareUnfreezeRequest;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareService;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareService::class)]
class ProfitShareServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }

    public function testRequestProfitShare(): void
    {
        // 创建Mock依赖
        $orderRepository = $this->createMock(ProfitShareOrderRepository::class);
        $orderRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null)
        ;
        $orderRepository->expects($this->once())
            ->method('save')
            ->with(self::isInstanceOf(ProfitShareOrder::class))
        ;

        $operationRepository = $this->createMock(ProfitShareOperationLogRepository::class);
        $operationRepository->expects($this->once())->method('save');

        $builder = new FakeBuilderChainable([
            new Response(200, [], Json::encode([
                'order_id' => '3008450740201411110007820472',
                'state' => 'FINISHED',
                'receivers' => [
                    [
                        'amount' => 100,
                        'description' => '分账描述',
                        'type' => 'MERCHANT_ID',
                        'account' => '1900000109',
                        'result' => 'SUCCESS',
                        'detail_id' => '36011111111111111111111',
                        'create_time' => '2015-05-20T13:29:35+08:00',
                        'finish_time' => '2015-05-20T13:29:35+08:00',
                    ],
                ],
            ])),
        ]);

        $builderFactory = $this->createMock(WechatPayBuilder::class);
        $builderFactory->expects($this->once())
            ->method('genBuilder')
            ->willReturn($builder)
        ;
        $logger = $this->createMock(LoggerInterface::class);

        // 将Mock依赖注入到容器中
        self::getContainer()->set(ProfitShareOrderRepository::class, $orderRepository);
        self::getContainer()->set(ProfitShareOperationLogRepository::class, $operationRepository);
        self::getContainer()->set(WechatPayBuilder::class, $builderFactory);
        self::getContainer()->set(LoggerInterface::class, $logger);

        // 从容器获取服务
        $service = self::getService(ProfitShareService::class);

        $request = new ProfitShareOrderRequest(
            subMchId: '1900000109',
            transactionId: '4208450740201411110007820472',
            outOrderNo: 'P20150806125346',
        );
        $request->addReceiver(new ProfitShareReceiverRequest(
            type: 'MERCHANT_ID',
            account: '1900000109',
            amount: 100,
            description: '分账描述',
        ));

        $merchant = new Merchant();
        $merchant->setMchId('1900000001');
        $merchant->setPemKey('fake-key');
        $merchant->setPemCert('fake-cert');
        $merchant->setCertSerial('ABC');

        $order = $service->requestProfitShare($merchant, $request);
        $this->assertSame(ProfitShareOrderState::FINISHED, $order->getState());
        $this->assertSame('3008450740201411110007820472', $order->getOrderId());
        $this->assertCount(1, $order->getReceivers());
    }

    public function testQueryProfitShareOrderCreatesEntityWhenMissing(): void
    {
        // 创建Mock依赖
        $orderRepository = $this->createMock(ProfitShareOrderRepository::class);
        $orderRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null)
        ;
        $orderRepository->expects($this->once())
            ->method('save')
            ->with(self::isInstanceOf(ProfitShareOrder::class))
        ;

        $operationRepository = $this->createMock(ProfitShareOperationLogRepository::class);
        $operationRepository->expects($this->once())->method('save');

        $builder = new FakeBuilderChainable([
            new Response(200, [], Json::encode([
                'order_id' => '3008450740201411110007820472',
                'state' => 'FINISHED',
                'receivers' => [],
            ])),
        ]);
        $builderFactory = $this->createMock(WechatPayBuilder::class);
        $builderFactory->expects($this->once())
            ->method('genBuilder')
            ->willReturn($builder)
        ;
        $logger = $this->createMock(LoggerInterface::class);

        // 将Mock依赖注入到容器中
        self::getContainer()->set(ProfitShareOrderRepository::class, $orderRepository);
        self::getContainer()->set(ProfitShareOperationLogRepository::class, $operationRepository);
        self::getContainer()->set(WechatPayBuilder::class, $builderFactory);
        self::getContainer()->set(LoggerInterface::class, $logger);

        // 从容器获取服务
        $service = self::getService(ProfitShareService::class);

        $merchant = new Merchant();
        $merchant->setMchId('1900000001');
        $merchant->setPemKey('fake-key');
        $merchant->setPemCert('fake-cert');
        $merchant->setCertSerial('ABC');

        $order = $service->queryProfitShareOrder(
            $merchant,
            '1900000109',
            'P20150806125346',
            '4208450740201411110007820472'
        );

        $this->assertSame('3008450740201411110007820472', $order->getOrderId());
    }

    public function testUnfreezeRemainingAmount(): void
    {
        // 创建Mock依赖
        $orderRepository = $this->createMock(ProfitShareOrderRepository::class);
        $orderRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null)
        ;
        $orderRepository->expects($this->once())->method('save');

        $operationRepository = $this->createMock(ProfitShareOperationLogRepository::class);
        $operationRepository->expects($this->once())->method('save');

        $builder = new FakeBuilderChainable([
            new Response(200, [], Json::encode([
                'order_id' => '3008450740201411110007820472',
                'state' => 'FINISHED',
                'receivers' => [],
            ])),
        ]);
        $builderFactory = $this->createMock(WechatPayBuilder::class);
        $builderFactory->expects($this->once())
            ->method('genBuilder')
            ->willReturn($builder)
        ;
        $logger = $this->createMock(LoggerInterface::class);

        // 将Mock依赖注入到容器中
        self::getContainer()->set(ProfitShareOrderRepository::class, $orderRepository);
        self::getContainer()->set(ProfitShareOperationLogRepository::class, $operationRepository);
        self::getContainer()->set(WechatPayBuilder::class, $builderFactory);
        self::getContainer()->set(LoggerInterface::class, $logger);

        // 从容器获取服务
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

        $result = $service->unfreezeRemainingAmount($merchant, $request);
        $this->assertSame('3008450740201411110007820472', $result->getOrderId());
    }
}
