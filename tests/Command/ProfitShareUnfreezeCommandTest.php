<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Command;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\WechatPayProfitShareBundle\Command\ProfitShareUnfreezeCommand;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOrderRepository;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareUnfreezeRequest;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareService;
use WechatPayBundle\Entity\Merchant;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareUnfreezeCommand::class)]
class ProfitShareUnfreezeCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    /** @phpstan-var MockObject&ProfitShareOrderRepository */
    private ProfitShareOrderRepository $orderRepository;

    /** @phpstan-var MockObject&ProfitShareService */
    private ProfitShareService $profitShareService;

    /** @phpstan-var MockObject&LoggerInterface */
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

        $this->profitShareService->expects($this->never())
            ->method('unfreezeRemainingAmount')
        ;

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertStringContainsString('解冻成功数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    /**
     * @return MockObject&ProfitShareOrder
     */
    private function createMockOrder(): ProfitShareOrder
    {
        /** @phpstan-var MockObject&ProfitShareOrder $order */
        $order = $this->createMock(ProfitShareOrder::class);
        $order->method('isUnfreezeUnsplit')->willReturn(false);
        $order->method('getSubMchId')->willReturn('1234567890');
        $order->method('getTransactionId')->willReturn('TX1234567890');
        $order->method('getOutOrderNo')->willReturn('ORDER123');

        return $order;
    }

    /**
     * @return MockObject&ProfitShareOrder
     */
    private function createMockAlreadyUnfrozenOrder(): ProfitShareOrder
    {
        /** @phpstan-var MockObject&ProfitShareOrder $order */
        $order = $this->createMock(ProfitShareOrder::class);
        $order->method('isUnfreezeUnsplit')->willReturn(true); // 已解冻
        $order->method('getSubMchId')->willReturn('1234567890');
        $order->method('getTransactionId')->willReturn('TX1234567890');
        $order->method('getOutOrderNo')->willReturn('ORDER123');

        return $order;
    }

    /**
     * @return MockObject&ProfitShareOrder
     */
    private function createMockOrderWithMerchant(): ProfitShareOrder
    {
        $merchant = $this->createMockMerchant();
        /** @phpstan-var MockObject&ProfitShareOrder $order */
        $order = $this->createMock(ProfitShareOrder::class);
        $order->method('isUnfreezeUnsplit')->willReturn(false);
        $order->method('getMerchant')->willReturn($merchant);
        $order->method('getSubMchId')->willReturn('1234567890');
        $order->method('getTransactionId')->willReturn('TX1234567890');
        $order->method('getOutOrderNo')->willReturn('ORDER123');

        return $order;
    }

    /**
     * @return MockObject&ProfitShareOrder
     */
    private function createMockUnfrozenOrder(): ProfitShareOrder
    {
        /** @phpstan-var MockObject&ProfitShareOrder $order */
        $order = $this->createMock(ProfitShareOrder::class);
        $order->method('isUnfreezeUnsplit')->willReturn(true); // 已解冻
        $order->method('getSubMchId')->willReturn('1234567890');
        $order->method('getTransactionId')->willReturn('TX1234567890');
        $order->method('getOutOrderNo')->willReturn('ORDER123');

        return $order;
    }

    /**
     * @return MockObject&Merchant
     */
    private function createMockMerchant(): Merchant
    {
        /** @phpstan-var MockObject&Merchant $merchant */
        $merchant = $this->createMock(Merchant::class);

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
        /** @phpstan-var MockObject&Query $query */
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn($orders);

        /** @phpstan-var MockObject&QueryBuilder $qb */
        $qb = $this->createMock(QueryBuilder::class);
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
            ->with('订单已解冻，跳过处理', self::isArray())
        ;

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
            ->with(self::isInstanceOf(Merchant::class), self::isInstanceOf(ProfitShareUnfreezeRequest::class))
            ->willReturn($unfrozenOrder)
        ;

        $this->logger->expects($this->once())
            ->method('info')
            ->with('分账资金解冻成功', self::isArray())
        ;

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('解冻成功数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithUnfreezeStatusUnknown(): void
    {
        $order = $this->createMockOrderWithMerchant();

        // 创建一个解冻状态未知的订单（isUnfreezeUnsplit返回false）
        /** @phpstan-var MockObject&ProfitShareOrder $unfrozenOrder */
        $unfrozenOrder = $this->createMock(ProfitShareOrder::class);
        $unfrozenOrder->method('isUnfreezeUnsplit')->willReturn(false); // 关键：未解冻状态

        $this->setupOrderRepository([$order]);

        $this->profitShareService->expects($this->once())
            ->method('unfreezeRemainingAmount')
            ->with(self::isInstanceOf(Merchant::class), self::isInstanceOf(ProfitShareUnfreezeRequest::class))
            ->willReturn($unfrozenOrder)
        ;

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('分账资金解冻状态未知', self::isArray())
        ;

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('解冻失败数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithMissingMerchant(): void
    {
        $order = $this->createMockOrder();

        $this->setupOrderRepository([$order]);

        /** @phpstan-var MockObject&ProfitShareOrder $order */
        $order->method('getMerchant')->willReturn(null);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('分账订单缺少商户信息', self::isArray())
        ;

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

        /** @phpstan-var MockObject&ProfitShareOrder $order */
        $order->method('getMerchant')->willReturn($merchant);

        $this->profitShareService->expects($this->once())
            ->method('unfreezeRemainingAmount')
            ->willThrowException(new \RuntimeException('Unfreeze error'))
        ;

        $this->logger->expects($this->once())
            ->method('error')
            ->with('分账资金解冻失败', self::isArray())
        ;

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
            ->willReturn($order)
        ;

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
        /** @phpstan-var MockObject&Query $query */
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn($orders);

        /** @phpstan-var MockObject&QueryBuilder $qb */
        $qb = $this->createMock(QueryBuilder::class);
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

        $this->profitShareService->expects($this->never())
            ->method('unfreezeRemainingAmount')
        ;

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

        $this->profitShareService->expects($this->never())
            ->method('unfreezeRemainingAmount')
        ;

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

        $this->profitShareService->expects($this->never())
            ->method('unfreezeRemainingAmount')
        ;

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
            ->willReturn($order)
        ;

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
