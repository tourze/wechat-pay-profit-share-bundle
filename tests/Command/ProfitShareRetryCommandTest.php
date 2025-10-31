<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Command;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\WechatPayProfitShareBundle\Command\ProfitShareRetryCommand;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReceiver;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareReceiverResult;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareReceiverRepository;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareService;
use WechatPayBundle\Entity\Merchant;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareRetryCommand::class)]
class ProfitShareRetryCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;
    private ProfitShareReceiverRepository $receiverRepository;
    private ProfitShareService $profitShareService;
    private LoggerInterface $logger;

    public function testExecuteWithNoReceivers(): void
    {
        $this->receiverRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->createQueryBuilderMock([]));

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('没有需要重试的分账接收方', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    /**
     * @param ProfitShareReceiver[] $receivers
     */
    private function createQueryBuilderMock(array $receivers): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->method('innerJoin')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();

        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getResult')->willReturn($receivers);
        $qb->method('getQuery')->willReturn($query);

        return $qb;
    }

    public function testExecuteWithDryRun(): void
    {
        $receiver = $this->createMockReceiver();
        $order = $this->createMockOrder();
        $merchant = $this->createMockMerchant();

        $this->setupReceiverRepository([$receiver]);

        $receiver->method('getOrder')->willReturn($order);
        $receiver->method('getRetryCount')->willReturn(0);
        $receiver->method('getNextRetryAt')->willReturn(null);
        // $receiver->method('getUpdatedAt')->willReturn(new \DateTimeImmutable('-1 hour'));

        $order->method('getMerchant')->willReturn($merchant);
        $order->method('getSubMchId')->willReturn('1234567890');
        $order->method('getOutOrderNo')->willReturn('ORDER123');
        $order->method('getTransactionId')->willReturn('TX1234567890');

        $this->profitShareService->expects($this->never())
            ->method('queryProfitShareOrder');

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertStringContainsString('重试成功数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    private function createMockReceiver(): ProfitShareReceiver
    {
        $receiver = $this->createMock(ProfitShareReceiver::class);
        $receiver->method('getId')->willReturn('1');
        return $receiver;
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

    /**
     * @param ProfitShareReceiver[] $receivers
     */
    private function setupReceiverRepository(array $receivers): void
    {
        $this->receiverRepository->method('createQueryBuilder')->willReturn($this->createQueryBuilderMock($receivers));
    }

    public function testExecuteWithMaxRetryReached(): void
    {
        $receiver = $this->createMockReceiver();
        $order = $this->createMockOrder();

        $this->setupReceiverRepository([$receiver]);

        $receiver->method('getOrder')->willReturn($order);
        $receiver->method('getRetryCount')->willReturn(3); // 达到最大重试次数
        $receiver->method('isFinallyFailed')->willReturn(false);

        $receiver->expects($this->once())
            ->method('setFinallyFailed')
            ->with(true);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('分账接收方达到最大重试次数，标记为最终失败', self::isArray());

        $this->commandTester->execute(['--max-retry' => 3]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('最终失败数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithSuccessfulRetry(): void
    {
        $receiver = $this->createMockReceiver();
        $order = $this->createMockOrder();
        $merchant = $this->createMockMerchant();
        $updatedOrder = $this->createMockOrder();
        $updatedReceiver = $this->createMockReceiver();

        $this->setupReceiverRepository([$receiver]);

        $receiver->method('getOrder')->willReturn($order);
        $receiver->method('getRetryCount')->willReturn(0);
        $receiver->method('getNextRetryAt')->willReturn(null);
        // $receiver->method('getUpdatedAt')->willReturn(new \DateTimeImmutable('-1 hour'));
        $receiver->method('getAccount')->willReturn('test_account');
        $receiver->method('getAmount')->willReturn(100);

        $order->method('getMerchant')->willReturn($merchant);
        $order->method('getSubMchId')->willReturn('1234567890');
        $order->method('getOutOrderNo')->willReturn('ORDER123');
        $order->method('getTransactionId')->willReturn('TX1234567890');

        $this->profitShareService->expects($this->once())
            ->method('queryProfitShareOrder')
            ->with($merchant, '1234567890', 'ORDER123', 'TX1234567890')
            ->willReturn($updatedOrder);

        $updatedOrder->method('getReceivers')->willReturn(new ArrayCollection([$updatedReceiver]));

        $updatedReceiver->method('getAccount')->willReturn('test_account');
        $updatedReceiver->method('getAmount')->willReturn(100);
        $updatedReceiver->method('getResult')->willReturn(ProfitShareReceiverResult::SUCCESS);

        $receiver->expects($this->once())
            ->method('setRetryCount')
            ->with(1);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('分账接收方重试成功', self::isArray());

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('重试成功数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithFailedRetry(): void
    {
        $receiver = $this->createMockReceiver();
        $order = $this->createMockOrder();
        $merchant = $this->createMockMerchant();
        $updatedOrder = $this->createMockOrder();
        $updatedReceiver = $this->createMockReceiver();

        $this->setupReceiverRepository([$receiver]);

        $receiver->method('getOrder')->willReturn($order);
        $receiver->method('getRetryCount')->willReturn(0);
        $receiver->method('getNextRetryAt')->willReturn(null);
        // $receiver->method('getUpdatedAt')->willReturn(new \DateTimeImmutable('-1 hour'));
        $receiver->method('getAccount')->willReturn('test_account');
        $receiver->method('getAmount')->willReturn(100);

        $order->method('getMerchant')->willReturn($merchant);
        $order->method('getSubMchId')->willReturn('1234567890');
        $order->method('getOutOrderNo')->willReturn('ORDER123');
        $order->method('getTransactionId')->willReturn('TX1234567890');

        $this->profitShareService->expects($this->once())
            ->method('queryProfitShareOrder')
            ->with($merchant, '1234567890', 'ORDER123', 'TX1234567890')
            ->willReturn($updatedOrder);

        $updatedOrder->method('getReceivers')->willReturn(new ArrayCollection([$updatedReceiver]));

        $updatedReceiver->method('getAccount')->willReturn('test_account');
        $updatedReceiver->method('getAmount')->willReturn(100);
        $updatedReceiver->method('getResult')->willReturn(ProfitShareReceiverResult::FAILED);

        $receiver->expects($this->atLeastOnce())
            ->method('setRetryCount')
            ->with(self::greaterThan(0));

        $receiver->expects($this->once())
            ->method('setNextRetryAt')
            ->with(self::isInstanceOf(\DateTimeImmutable::class));

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('分账接收方重试失败', self::isArray());

        $this->commandTester->execute(['--retry-interval' => 30]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('重试失败数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithMissingOrder(): void
    {
        $receiver = $this->createMockReceiver();

        $this->setupReceiverRepository([$receiver]);

        $receiver->method('getOrder')->willReturn(null);
        $receiver->method('getRetryCount')->willReturn(0);
        $receiver->method('getNextRetryAt')->willReturn(null);
        // $receiver->method('getUpdatedAt')->willReturn(new \DateTimeImmutable('-1 hour'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('分账接收方缺少订单信息', self::isArray());

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('重试失败数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithMissingMerchant(): void
    {
        $receiver = $this->createMockReceiver();
        $order = $this->createMockOrder();

        $this->setupReceiverRepository([$receiver]);

        $receiver->method('getOrder')->willReturn($order);
        $receiver->method('getRetryCount')->willReturn(0);
        $receiver->method('getNextRetryAt')->willReturn(null);
        // $receiver->method('getUpdatedAt')->willReturn(new \DateTimeImmutable('-1 hour'));
        $order->method('getMerchant')->willReturn(null);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('分账订单缺少商户信息', self::isArray());

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('重试失败数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithServiceException(): void
    {
        $receiver = $this->createMockReceiver();
        $order = $this->createMockOrder();
        $merchant = $this->createMockMerchant();

        $this->setupReceiverRepository([$receiver]);

        $receiver->method('getOrder')->willReturn($order);
        $receiver->method('getRetryCount')->willReturn(0);
        $receiver->method('getNextRetryAt')->willReturn(null);
        // $receiver->method('getUpdatedAt')->willReturn(new \DateTimeImmutable('-1 hour'));
        $receiver->method('getAccount')->willReturn('test_account');
        $receiver->method('getAmount')->willReturn(100);

        $order->method('getMerchant')->willReturn($merchant);
        $order->method('getSubMchId')->willReturn('1234567890');
        $order->method('getOutOrderNo')->willReturn('ORDER123');
        $order->method('getTransactionId')->willReturn('TX1234567890');

        $this->profitShareService->expects($this->once())
            ->method('queryProfitShareOrder')
            ->willThrowException(new \RuntimeException('Service error'));

        $receiver->expects($this->once())
            ->method('setRetryCount')
            ->with(1);

        $receiver->expects($this->once())
            ->method('setNextRetryAt')
            ->with(self::isInstanceOf(\DateTimeImmutable::class));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('重试分账接收方失败', self::isArray());

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('重试失败数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionDryRun(): void
    {
        $receiver = $this->createMockReceiver();
        $order = $this->createMockOrder();
        $merchant = $this->createMockMerchant();

        $this->setupReceiverRepository([$receiver]);

        $receiver->method('getOrder')->willReturn($order);
        $receiver->method('getRetryCount')->willReturn(0);
        $receiver->method('getNextRetryAt')->willReturn(null);
        $receiver->method('getAccount')->willReturn('test_account');
        $receiver->method('getAmount')->willReturn(100);

        $order->method('getMerchant')->willReturn($merchant);
        $order->method('getSubMchId')->willReturn('1234567890');
        $order->method('getOutOrderNo')->willReturn('ORDER123');
        $order->method('getTransactionId')->willReturn('TX1234567890');

        $this->profitShareService->expects($this->never())
            ->method('queryProfitShareOrder');

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertStringContainsString('重试成功数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionMaxRetry(): void
    {
        $receiver = $this->createMockReceiver();
        $order = $this->createMockOrder();

        $this->setupReceiverRepository([$receiver]);

        $receiver->method('getOrder')->willReturn($order);
        $receiver->method('getRetryCount')->willReturn(5); // 超过默认最大重试次数
        $receiver->method('isFinallyFailed')->willReturn(false);
        $receiver->method('getNextRetryAt')->willReturn(null);

        $receiver->expects($this->once())
            ->method('setFinallyFailed')
            ->with(true);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('分账接收方达到最大重试次数，标记为最终失败', self::isArray());

        $this->commandTester->execute(['--max-retry' => 5]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('最终失败数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionMerchantId(): void
    {
        $this->setupReceiverRepository([]);

        $this->commandTester->execute(['--merchant-id' => '123']);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('没有需要重试的分账接收方', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionRetryInterval(): void
    {
        $receiver = $this->createMockReceiver();
        $order = $this->createMockOrder();
        $merchant = $this->createMockMerchant();

        $this->setupReceiverRepository([$receiver]);

        $receiver->method('getOrder')->willReturn($order);
        $receiver->method('getRetryCount')->willReturn(0);
        $receiver->method('getNextRetryAt')->willReturn(null);
        $receiver->method('getAccount')->willReturn('test_account');
        $receiver->method('getAmount')->willReturn(100);

        $order->method('getMerchant')->willReturn($merchant);
        $order->method('getSubMchId')->willReturn('1234567890');
        $order->method('getOutOrderNo')->willReturn('ORDER123');
        $order->method('getTransactionId')->willReturn('TX1234567890');

        $this->profitShareService->expects($this->once())
            ->method('queryProfitShareOrder')
            ->with($merchant, '1234567890', 'ORDER123', 'TX1234567890')
            ->willThrowException(new \RuntimeException('Service error'));

        $receiver->expects($this->once())
            ->method('setRetryCount')
            ->with(1);

        // 验证重试间隔设置为60分钟（而不是默认的30分钟）
        $receiver->expects($this->once())
            ->method('setNextRetryAt')
            ->with(self::callback(function (\DateTimeImmutable $nextRetryAt) {
                $now = new \DateTimeImmutable();
                $expectedTime = $now->modify('+60 minutes');
                // 允许1分钟的误差
                $diff = abs($nextRetryAt->getTimestamp() - $expectedTime->getTimestamp());
                return $diff <= 60;
            }));

        $this->commandTester->execute(['--retry-interval' => 60]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('重试失败数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        $this->receiverRepository = $this->createMock(ProfitShareReceiverRepository::class);
        $this->profitShareService = $this->createMock(ProfitShareService::class);
        // Mock EntityManager 以避免与 Mock 对象的冲突
        $entityManager = self::getEntityManager();
        $this->logger = $this->createMock(LoggerInterface::class);

        // 清理服务定位器缓存，防止服务重复初始化
        self::clearServiceLocatorCache();

        // 设置Mock服务到容器
        $container = self::getContainer();

        // 检查服务是否已经初始化，避免重复设置
        if (!$container->initialized(ProfitShareReceiverRepository::class)) {
            $container->set(ProfitShareReceiverRepository::class, $this->receiverRepository);
        }
        if (!$container->initialized(ProfitShareService::class)) {
            $container->set(ProfitShareService::class, $this->profitShareService);
        }
        // 对于 EntityManager 和 Logger，使用 try-catch 处理，因为它们可能已经被其他测试初始化
        try {
            $container->set('doctrine.orm.default_entity_manager', $entityManager);
        } catch (\Symfony\Component\DependencyInjection\Exception\InvalidArgumentException $e) {
            // 使用现有的EntityManager - 不需要额外操作
        }
        try {
            $container->set('logger', $this->logger);
        } catch (\Symfony\Component\DependencyInjection\Exception\InvalidArgumentException $e) {
            // 使用现有的logger
            /** @var LoggerInterface $logger */
            $logger = $container->get('logger');
            $this->logger = $logger;
        }

        /** @var ProfitShareRetryCommand $command */
        $command = self::getService(ProfitShareRetryCommand::class);

        $application = new Application();
        $application->add($command);
        $this->commandTester = new CommandTester($command);
    }
}
