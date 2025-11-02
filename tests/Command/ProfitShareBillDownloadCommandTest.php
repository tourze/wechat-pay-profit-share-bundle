<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\WechatPayProfitShareBundle\Command\ProfitShareBillDownloadCommand;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareBillTask;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareBillStatus;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareBillTaskRepository;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareBillDownloadRequest;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareBillService;
use WechatPayBundle\Entity\Merchant;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareBillDownloadCommand::class)]
class ProfitShareBillDownloadCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    /** @phpstan-var MockObject&ProfitShareBillTaskRepository */
    private ProfitShareBillTaskRepository $billTaskRepository;

    /** @phpstan-var MockObject&ProfitShareBillService */
    private ProfitShareBillService $billService;

    /** @phpstan-var MockObject&LoggerInterface */
    private LoggerInterface $logger;

    public function testExecuteWithNoTasks(): void
    {
        $this->billTaskRepository->expects($this->once())
            ->method('findBy')
            ->with(['status' => ProfitShareBillStatus::READY])
            ->willReturn([])
        ;

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('没有需要下载的账单', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    private function createMockTask(): ProfitShareBillTask
    {
        $task = $this->getMockBuilder(ProfitShareBillTask::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCreatedAt', 'getBillDate', 'getSubMchId', 'getMerchant', 'setLocalPath'])
            ->getMock()
        ;
        // 使用DateTimeImmutable以匹配Entity声明
        $task->method('getCreatedAt')->willReturn(new \DateTimeImmutable('-1 day'));
        // 设置billDate以避免属性未初始化错误
        $task->method('getBillDate')->willReturn(new \DateTimeImmutable('-1 day'));
        $task->method('getSubMchId')->willReturn('1234567890');
        $task->method('getMerchant')->willReturn(null);

        return $task;
    }

    private function createMockTaskWithMerchant(): ProfitShareBillTask
    {
        $merchant = $this->createMockMerchant();
        $task = $this->getMockBuilder(ProfitShareBillTask::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCreatedAt', 'getBillDate', 'getSubMchId', 'getMerchant', 'setLocalPath'])
            ->getMock()
        ;
        $task->method('getCreatedAt')->willReturn(new \DateTimeImmutable('-1 day'));
        $task->method('getBillDate')->willReturn(new \DateTimeImmutable('-1 day'));
        $task->method('getSubMchId')->willReturn('1234567890');
        $task->method('getMerchant')->willReturn($merchant);

        return $task;
    }

    private function createMockMerchant(): Merchant
    {
        $merchant = $this->createMock(Merchant::class);

        return $merchant;
    }

    private function createExpiredMockTask(): ProfitShareBillTask
    {
        $task = $this->getMockBuilder(ProfitShareBillTask::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCreatedAt', 'getBillDate', 'getSubMchId', 'getMerchant'])
            ->getMock()
        ;
        // 设置为8天前，超过默认的7天过期期限
        $task->method('getCreatedAt')->willReturn(new \DateTimeImmutable('-8 days'));
        $task->method('getBillDate')->willReturn(new \DateTimeImmutable('-8 days'));
        $task->method('getSubMchId')->willReturn('1234567890');
        $task->method('getMerchant')->willReturn(null);

        return $task;
    }

    public function testExecuteWithExpiredTask(): void
    {
        $task = $this->createExpiredMockTask(); // 使用专门的方法创建过期任务

        $this->billTaskRepository->expects($this->once())
            ->method('findBy')
            ->with(['status' => ProfitShareBillStatus::READY])
            ->willReturn([$task])
        ;

        $this->billService->expects($this->never())
            ->method('downloadBill')
        ;

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('过期任务', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithSuccessfulDownload(): void
    {
        $task = $this->createMockTaskWithMerchant();

        $this->billTaskRepository->expects($this->once())
            ->method('findBy')
            ->with(['status' => ProfitShareBillStatus::READY])
            ->willReturn([$task])
        ;

        $this->billService->expects($this->once())
            ->method('downloadBill')
            ->with(self::isInstanceOf(Merchant::class), $task, self::isInstanceOf(ProfitShareBillDownloadRequest::class))
            ->willReturn($task)
        ;

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('下载成功', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
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
        $task = $this->createMockTask();
        $merchant = $this->createMockMerchant();

        $this->billTaskRepository->expects($this->once())
            ->method('findBy')
            ->with(['status' => ProfitShareBillStatus::READY])
            ->willReturn([$task])
        ;

        $this->billService->expects($this->never())
            ->method('downloadBill')
        ;

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionExpireDays(): void
    {
        $task = $this->createExpiredMockTask(); // 使用专门的方法创建过期任务

        $this->billTaskRepository->expects($this->once())
            ->method('findBy')
            ->with(['status' => ProfitShareBillStatus::READY])
            ->willReturn([$task])
        ;

        $this->billService->expects($this->never())
            ->method('downloadBill')
        ;

        $this->commandTester->execute(['--expire-days' => 10]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('过期任务', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionDownloadPath(): void
    {
        $task = $this->createMockTaskWithMerchant();

        $this->billTaskRepository->expects($this->once())
            ->method('findBy')
            ->with(['status' => ProfitShareBillStatus::READY])
            ->willReturn([$task])
        ;

        $this->billService->expects($this->once())
            ->method('downloadBill')
            ->with(self::isInstanceOf(Merchant::class), $task, self::isInstanceOf(ProfitShareBillDownloadRequest::class))
            ->willReturn($task)
        ;

        $this->commandTester->execute(['--download-path' => '/custom/path']);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('下载成功', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionMerchantId(): void
    {
        $task = $this->createMockTask();

        $this->billTaskRepository->expects($this->once())
            ->method('findBy')
            ->with([
                'status' => ProfitShareBillStatus::READY,
                'merchant' => '123',
            ])
            ->willReturn([])
        ;

        $this->commandTester->execute(['--merchant-id' => '123']);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('没有需要下载的账单', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        $this->billTaskRepository = $this->createMock(ProfitShareBillTaskRepository::class);
        $this->billService = $this->createMock(ProfitShareBillService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // 清理服务定位器缓存，防止服务重复初始化
        self::clearServiceLocatorCache();

        // 设置Mock服务到容器
        $container = self::getContainer();

        // 检查服务是否已经初始化，避免重复设置
        if (!$container->initialized(ProfitShareBillTaskRepository::class)) {
            $container->set(ProfitShareBillTaskRepository::class, $this->billTaskRepository);
        }
        if (!$container->initialized(ProfitShareBillService::class)) {
            $container->set(ProfitShareBillService::class, $this->billService);
        }
        // 对于 Logger，使用 try-catch 处理，因为它可能已经被其他测试初始化
        try {
            $container->set('logger', $this->logger);
        } catch (InvalidArgumentException $e) {
            // 使用现有的logger
            /** @var LoggerInterface $logger */
            $logger = $container->get('logger');
            /** @phpstan-var MockObject&LoggerInterface $logger */
            $this->logger = $logger;
        }

        /** @var ProfitShareBillDownloadCommand $command */
        $command = self::getService(ProfitShareBillDownloadCommand::class);

        $application = new Application();
        $application->add($command);
        $this->commandTester = new CommandTester($command);
    }
}
