<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\WechatPayProfitShareBundle\Command\ProfitShareUnfreezeCommand;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOrderRepository;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareService;
use WechatPayBundle\Entity\Merchant;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareUnfreezeCommand::class)]
class ProfitShareUnfreezeCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;
    private ProfitShareOrderRepository $orderRepository;
    private ProfitShareService $profitShareService;
    private LoggerInterface $logger;

    public function testExecuteWithNoOrders(): void
    {
        $this->setupOrderRepository([]);

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('没有需要解冻的订单', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithDryRun(): void
    {
        $order = $this->createMockOrder();
        $merchant = $this->createMockMerchant();

        $this->setupOrderRepository([$order]);

        $order->method('isUnfreezeUnsplit')->willReturn(false);

        $this->profitShareService->expects($this->never())
            ->method('unfreezeRemainingAmount');

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertStringContainsString('解冻成功数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    private function createMockOrder(): ProfitShareOrder
    {
        $order = $this->createMock(ProfitShareOrder::class);
        $order->method('getId')->willReturn('1');
        $order->method('isUnfreezeUnsplit')->willReturn(false);
        $order->method('getSubMchId')->willReturn('1234567890');
        $order->method('getTransactionId')->willReturn('TX1234567890');
        $order->method('getOutOrderNo')->willReturn('ORDER123');
        return $order;
    }

    private function createMockAlreadyUnfrozenOrder(): ProfitShareOrder
    {
        $order = $this->createMock(ProfitShareOrder::class);
        $order->method('getId')->willReturn('1');
        $order->method('isUnfreezeUnsplit')->willReturn(true); // 已解冻
        $order->method('getSubMchId')->willReturn('1234567890');
        $order->method('getTransactionId')->willReturn('TX1234567890');
        $order->method('getOutOrderNo')->willReturn('ORDER123');
        return $order;
    }

    private function createMockOrderWithMerchant(): ProfitShareOrder
    {
        $merchant = $this->createMockMerchant();
        $order = $this->createMock(ProfitShareOrder::class);
        $order->method('getId')->willReturn('1');
        $order->method('isUnfreezeUnsplit')->willReturn(false);
        $order->method('getMerchant')->willReturn($merchant);
        $order->method('getSubMchId')->willReturn('1234567890');
        $order->method('getTransactionId')->willReturn('TX1234567890');
        $order->method('getOutOrderNo')->willReturn('ORDER123');
        return $order;
    }

    private function createMockUnfrozenOrder(): ProfitShareOrder
    {
        $order = $this->createMock(ProfitShareOrder::class);
        $order->method('getId')->willReturn('1');
        $order->method('isUnfreezeUnsplit')->willReturn(true); // 已解冻
        $order->method('getSubMchId')->willReturn('1234567890');
        $order->method('getTransactionId')->willReturn('TX1234567890');
        $order->method('getOutOrderNo')->willReturn('ORDER123');
        return $order;
    }

    private function createMockMerchant(): Merchant
    {
        $merchant = $this->createMock(Merchant::class);
        $merchant->method('getId')->willReturn('1');
        return $merchant;
    }

    /**
     * @param ProfitShareOrder[] $orders
     */
    private function setupOrderRepository(array $orders): void
    {
        $this->setupOrderRepositoryWithCustomTime($orders, 48);
    }

    /**
     * @param ProfitShareOrder[] $orders
     */
    private function setupOrderRepositoryWithCustomTime(array $orders, int $hours): void
    {
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getResult')->willReturn($orders);

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->method('innerJoin')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $this->orderRepository->method('createQueryBuilder')->willReturn($qb);
    }

    public function testExecuteWithAlreadyUnfrozenOrder(): void
    {
        $order = $this->createMockAlreadyUnfrozenOrder();

        $this->setupOrderRepository([$order]);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('订单已解冻，跳过处理', self::isArray());

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('跳过处理数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithSuccessfulUnfreeze(): void
    {
        $order = $this->createMockOrderWithMerchant();
        $unfrozenOrder = $this->createMockUnfrozenOrder();

        $this->setupOrderRepository([$order]);

        $this->profitShareService->expects($this->once())
            ->method('unfreezeRemainingAmount')
            ->with(self::isInstanceOf(Merchant::class), self::isInstanceOf(\Tourze\WechatPayProfitShareBundle\Request\ProfitShareUnfreezeRequest::class))
            ->willReturn($unfrozenOrder);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('分账资金解冻成功', self::isArray());

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('解冻成功数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithUnfreezeStatusUnknown(): void
    {
        $order = $this->createMockOrder();
        $merchant = $this->createMockMerchant();
        $unfrozenOrder = $this->createMockOrder();

        $this->setupOrderRepository([$order]);

        $order->method('isUnfreezeUnsplit')->willReturn(false);
        $order->method('getMerchant')->willReturn($merchant);
        $order->method('getSubMchId')->willReturn('1234567890');
        $order->method('getTransactionId')->willReturn('TX1234567890');
        $order->method('getOutOrderNo')->willReturn('ORDER123');

        $this->profitShareService->expects($this->once())
            ->method('unfreezeRemainingAmount')
            ->with($merchant, self::isInstanceOf(\Tourze\WechatPayProfitShareBundle\Request\ProfitShareUnfreezeRequest::class))
            ->willReturn($unfrozenOrder);

        $unfrozenOrder->method('isUnfreezeUnsplit')->willReturn(false);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('分账资金解冻状态未知', self::isArray());

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('解冻失败数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithMissingMerchant(): void
    {
        $order = $this->createMockOrder();

        $this->setupOrderRepository([$order]);

        $order->method('isUnfreezeUnsplit')->willReturn(false);
        $order->method('getMerchant')->willReturn(null);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('分账订单缺少商户信息', self::isArray());

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('解冻失败数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithServiceException(): void
    {
        $order = $this->createMockOrder();
        $merchant = $this->createMockMerchant();

        $this->setupOrderRepository([$order]);

        $order->method('isUnfreezeUnsplit')->willReturn(false);
        $order->method('getMerchant')->willReturn($merchant);
        $order->method('getSubMchId')->willReturn('1234567890');
        $order->method('getTransactionId')->willReturn('TX1234567890');
        $order->method('getOutOrderNo')->willReturn('ORDER123');

        $this->profitShareService->expects($this->once())
            ->method('unfreezeRemainingAmount')
            ->willThrowException(new \RuntimeException('Unfreeze error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('分账资金解冻失败', self::isArray());

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('解冻失败数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithForceUnfreeze(): void
    {
        $order = $this->createMockOrderWithMerchant();

        $this->setupOrderRepositoryWithoutTimeCheck([$order]);

        $this->profitShareService->expects($this->once())
            ->method('unfreezeRemainingAmount')
            ->willReturn($order);

        $this->commandTester->execute(['--force-unfreeze' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('总订单数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    /**
     * @param ProfitShareOrder[] $orders
     */
    private function setupOrderRepositoryWithoutTimeCheck(array $orders): void
    {
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getResult')->willReturn($orders);

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->method('innerJoin')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $this->orderRepository->method('createQueryBuilder')->willReturn($qb);
    }

    public function testExecuteWithSpecificMerchant(): void
    {
        $this->setupOrderRepository([]);

        $this->commandTester->execute(['--merchant-id' => '123']);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('没有需要解冻的订单', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithCustomUnfreezeHours(): void
    {
        $order = $this->createMockOrder();
        $merchant = $this->createMockMerchant();

        $this->setupOrderRepositoryWithCustomTime([$order], 24);

        $order->method('isUnfreezeUnsplit')->willReturn(false);

        $this->profitShareService->expects($this->never())
            ->method('unfreezeRemainingAmount');

        $this->commandTester->execute(['--unfreeze-hours' => 24]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('总订单数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionDryRun(): void
    {
        $order = $this->createMockOrder();
        $merchant = $this->createMockMerchant();

        $this->setupOrderRepository([$order]);

        $order->method('isUnfreezeUnsplit')->willReturn(false);

        $this->profitShareService->expects($this->never())
            ->method('unfreezeRemainingAmount');

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertStringContainsString('解冻成功数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionUnfreezeHours(): void
    {
        $order = $this->createMockOrder();

        $this->setupOrderRepositoryWithCustomTime([$order], 24);

        $order->method('isUnfreezeUnsplit')->willReturn(false);

        $this->profitShareService->expects($this->never())
            ->method('unfreezeRemainingAmount');

        $this->commandTester->execute(['--unfreeze-hours' => 24]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('总订单数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionForceUnfreeze(): void
    {
        $order = $this->createMockOrder();
        $merchant = $this->createMockMerchant();

        $this->setupOrderRepositoryWithoutTimeCheck([$order]);

        $this->profitShareService->expects($this->once())
            ->method('unfreezeRemainingAmount')
            ->willReturn($order);

        $this->commandTester->execute(['--force-unfreeze' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('总订单数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        $this->orderRepository = $this->createMock(ProfitShareOrderRepository::class);
        $this->profitShareService = $this->createMock(ProfitShareService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // 设置Mock服务到容器
        $container = self::getContainer();
        $container->set(ProfitShareOrderRepository::class, $this->orderRepository);
        $container->set(ProfitShareService::class, $this->profitShareService);
        $container->set(LoggerInterface::class, $this->logger);

        self::clearServiceLocatorCache();

        /** @var ProfitShareUnfreezeCommand $command */
        $command = self::getService(ProfitShareUnfreezeCommand::class);

        $application = new Application();
        $application->add($command);
        $this->commandTester = new CommandTester($command);
    }
}
