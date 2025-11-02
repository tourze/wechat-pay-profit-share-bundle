<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Service;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReturnOrder;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOperationLogRepository;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareReturnOrderRepository;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReturnRequest;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareReturnService;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareReturnService::class)]
class ProfitShareReturnServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }

    public function testRequestReturnCreatesEntity(): void
    {
        // 创建Mock依赖
        $returnRepository = $this->createMock(ProfitShareReturnOrderRepository::class);
        $returnRepository->expects($this->once())->method('findOneBy')->willReturn(null);
        $returnRepository->expects($this->once())->method('save');

        $operationRepository = $this->createMock(ProfitShareOperationLogRepository::class);
        $operationRepository->expects($this->once())->method('save');

        $builder = new FakeBuilderChainable([
            new Response(200, [], Json::encode([
                'order_id' => '3008450740201411110007820472',
                'out_order_no' => 'P20150806125346',
                'return_no' => 'R20150806125346',
                'amount' => 100,
                'description' => '回退说明',
                'result' => 'SUCCESS',
                'create_time' => '2015-05-20T13:29:35+08:00',
                'finish_time' => '2015-05-20T13:29:35+08:00',
            ])),
        ]);

        $builderFactory = $this->createMock(WechatPayBuilder::class);
        $builderFactory->expects($this->once())->method('genBuilder')->willReturn($builder);
        // 将Mock依赖注入到容器中
        self::getContainer()->set(ProfitShareReturnOrderRepository::class, $returnRepository);
        self::getContainer()->set(ProfitShareOperationLogRepository::class, $operationRepository);
        self::getContainer()->set(WechatPayBuilder::class, $builderFactory);

        // 从容器获取服务
        $service = self::getService(ProfitShareReturnService::class);

        $merchant = new Merchant();
        $merchant->setMchId('1900000001');

        $request = new ProfitShareReturnRequest(
            subMchId: '1900000109',
            outReturnNo: 'R20150806125346',
            amount: 100,
            description: '回退说明',
            orderId: '3008450740201411110007820472',
        );

        $order = $service->requestReturn($merchant, $request);
        $this->assertSame('3008450740201411110007820472', $order->getOrderId());
        $this->assertSame('R20150806125346', $order->getReturnNo());
    }

    public function testQueryReturnUpdatesEntity(): void
    {
        $returnOrder = new ProfitShareReturnOrder();
        $returnOrder->setOutReturnNo('R20150806125346');

        // 创建Mock依赖
        $returnRepository = $this->createMock(ProfitShareReturnOrderRepository::class);
        $returnRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['outReturnNo' => 'R20150806125346'])
            ->willReturn($returnOrder)
        ;
        $returnRepository->expects($this->once())->method('save');

        $operationRepository = $this->createMock(ProfitShareOperationLogRepository::class);
        $operationRepository->expects($this->once())->method('save');

        $builder = new FakeBuilderChainable([
            new Response(200, [], Json::encode([
                'order_id' => '3008450740201411110007820472',
                'out_order_no' => 'P20150806125346',
                'return_no' => 'R20150806125346',
                'amount' => 100,
                'description' => '回退说明',
                'result' => 'SUCCESS',
            ])),
        ]);

        $builderFactory = $this->createMock(WechatPayBuilder::class);
        $builderFactory->expects($this->once())->method('genBuilder')->willReturn($builder);
        // 将Mock依赖注入到容器中
        self::getContainer()->set(ProfitShareReturnOrderRepository::class, $returnRepository);
        self::getContainer()->set(ProfitShareOperationLogRepository::class, $operationRepository);
        self::getContainer()->set(WechatPayBuilder::class, $builderFactory);

        // 从容器获取服务
        $service = self::getService(ProfitShareReturnService::class);

        $merchant = new Merchant();
        $merchant->setMchId('1900000001');

        $result = $service->queryReturn(
            $merchant,
            '1900000109',
            'R20150806125346',
            outOrderNo: 'P20150806125346'
        );

        $this->assertSame('R20150806125346', $result->getReturnNo());
        $this->assertSame('SUCCESS', $result->getResult());
    }
}
