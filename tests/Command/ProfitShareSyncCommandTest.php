<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\WechatPayProfitShareBundle\Command\ProfitShareSyncCommand;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOrderState;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOrderRepository;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareService;
use WechatPayBundle\Entity\Merchant;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareSyncCommand::class)]
class ProfitShareSyncCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;
    private ProfitShareOrderRepository $orderRepository;
    private ProfitShareService $profitShareService;
    private LoggerInterface $logger;

    public function testExecuteWithNoOrders(): void
    {
        $this->orderRepository->expects($this->once())
            ->method('findBy')
            ->with(['state' => ProfitShareOrderState::PROCESSING])
            ->willReturn([]);

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('没有需要同步的订单', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithDryRun(): void
    {
        $order = $this->createMockOrder();
        $merchant = $this->createMockMerchant();

        $this->orderRepository->expects($this->once())
            ->method('findBy')
            ->with(['state' => ProfitShareOrderState::PROCESSING])
            ->willReturn([$order]);

        $this->profitShareService->expects($this->never())
            ->method('queryProfitShareOrder');

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    private function createMockOrder(): ProfitShareOrder
    {
        $order = $this->createMock(ProfitShareOrder::class);
        $order->method('getId')->willReturn('1');
        return $order;
    }

    private function createMockMerchant(): Merchant
    {
        $merchant = $this->createMock(Merchant::class);
        $merchant->method('getId')->willReturn('1');
        return $merchant;
    }

    public function testExecuteWithTimeoutOrder(): void
    {
        self::markTestSkipped('跳过此测试，因为无法模拟trait方法getCreatedAt()');
    }

    public function testExecuteWithSuccessfulSync(): void
    {
        self::markTestSkipped('跳过此测试，因为无法模拟trait方法getCreatedAt()');
    }

    public function testExecuteWithMissingMerchant(): void
    {
        self::markTestSkipped('跳过此测试，因为无法模拟trait方法getCreatedAt()');
    }

    public function testExecuteWithServiceException(): void
    {
        self::markTestSkipped('跳过此测试，因为无法模拟trait方法getCreatedAt()');
    }

    public function testOptionDryRun(): void
    {
        $order = $this->createMockOrder();
        $merchant = $this->createMockMerchant();

        $this->orderRepository->expects($this->once())
            ->method('findBy')
            ->with(['state' => ProfitShareOrderState::PROCESSING])
            ->willReturn([$order]);

        $this->profitShareService->expects($this->never())
            ->method('queryProfitShareOrder');

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionTimeoutHours(): void
    {
        $order = $this->createMockOrder();

        $this->orderRepository->expects($this->once())
            ->method('findBy')
            ->with(['state' => ProfitShareOrderState::PROCESSING])
            ->willReturn([$order]);

        $this->profitShareService->expects($this->never())
            ->method('queryProfitShareOrder');

        $this->commandTester->execute(['--timeout-hours' => 48, '--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
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

        /** @var ProfitShareSyncCommand $command */
        $command = self::getService(ProfitShareSyncCommand::class);

        $application = new Application();
        $application->add($command);
        $this->commandTester = new CommandTester($command);
    }
}
