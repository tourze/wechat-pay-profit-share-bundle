<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\WechatPayProfitShareBundle\Command\ProfitShareRetryCommand;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReceiver;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOrderState;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareReceiverResult;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareReceiverRepository;
use WechatPayBundle\Entity\Merchant;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareRetryCommand::class)]
final class ProfitShareRetryCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    private ProfitShareReceiverRepository $receiverRepository;

    /**
     * 清理 fixtures 加载的接收方数据
     */
    private function cleanReceiverData(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReceiver')->execute();
        self::getEntityManager()->createQuery('DELETE FROM Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder')->execute();
    }

    public function testExecuteWithNoReceivers(): void
    {
        $this->cleanReceiverData();

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('没有需要重试的分账接收方', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    private function createMerchant(?string $mchId = null): Merchant
    {
        // 直接从数据库查询 fixtures 中的商户，避免唯一约束冲突
        $merchantRepository = self::getEntityManager()->getRepository(Merchant::class);

        if (null === $mchId || '1234567890' === $mchId) {
            $merchant = $merchantRepository->findOneBy(['mchId' => '1234567890']);
            if (null !== $merchant) {
                return $merchant;
            }
        }

        if ('1111111111' === $mchId) {
            $merchant = $merchantRepository->findOneBy(['mchId' => '1111111111']);
            if (null !== $merchant) {
                return $merchant;
            }
        }

        if ('2222222222' === $mchId) {
            $merchant = $merchantRepository->findOneBy(['mchId' => '2222222222']);
            if (null !== $merchant) {
                return $merchant;
            }
        }

        // 如果指定了其他mchId或找不到现有商户，生成一个新的唯一商户
        $newMchId = $mchId ?? '5555555555'; // 使用指定的ID或默认不冲突的ID

        $merchant = new Merchant();
        $merchant->setMchId($newMchId);
        $merchant->setApiKey('test_api_key_' . $newMchId);
        $merchant->setApiKeyV3('test_api_key_v3_' . $newMchId);
        $merchant->setPemKey('test_pem_key_' . $newMchId);
        $merchant->setValid(true);

        $this->persistAndFlush($merchant);

        return $merchant;
    }

    private function createOrder(?Merchant $merchant = null): ProfitShareOrder
    {
        $order = new ProfitShareOrder();
        $order->setSubMchId('1234567890');
        $order->setTransactionId('TX1234567890');
        // 使用唯一的订单号避免唯一约束冲突
        $order->setOutOrderNo('ORDER_' . uniqid());
        $order->setState(ProfitShareOrderState::PROCESSING);
        if (null !== $merchant) {
            $order->setMerchant($merchant);
        }

        $this->persistAndFlush($order);

        return $order;
    }

    private function createReceiver(ProfitShareOrder $order, int $retryCount = 0): ProfitShareReceiver
    {
        $receiver = new ProfitShareReceiver();
        $receiver->setOrder($order);
        $receiver->setSequence(1);
        $receiver->setType('MERCHANT_ID');
        $receiver->setAccount('test_account');
        $receiver->setAmount(100);
        $receiver->setDescription('测试分账接收方');
        $receiver->setResult(ProfitShareReceiverResult::FAILED);
        $receiver->setRetryCount($retryCount);
        // 设置下次重试时间为过去时间，确保可以重试
        $receiver->setNextRetryAt(new \DateTimeImmutable('-1 hour'));

        $this->persistAndFlush($receiver);

        return $receiver;
    }

    public function testOptionDryRun(): void
    {
        $this->cleanReceiverData();

        $merchant = $this->createMerchant();
        $order = $this->createOrder($merchant);
        $receiver = $this->createReceiver($order);

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertStringContainsString('重试成功数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionMaxRetry(): void
    {
        $this->cleanReceiverData();

        $merchant = $this->createMerchant();
        $order = $this->createOrder($merchant);
        // 创建已达到最大重试次数的接收方
        $receiver = $this->createReceiver($order, 5);

        $this->commandTester->execute(['--max-retry' => 3]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('跳过处理数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithMissingOrder(): void
    {
        $this->cleanReceiverData();

        $merchant = $this->createMerchant();
        $order = $this->createOrder($merchant);
        $receiver = $this->createReceiver($order);

        // 手动删除订单，模拟数据不一致的情况
        self::getEntityManager()->remove($order);
        self::getEntityManager()->flush();

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertStringContainsString('没有需要重试的分账接收方', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithMissingMerchant(): void
    {
        $this->cleanReceiverData();

        $order = $this->createOrder(); // 没有设置merchant
        $receiver = $this->createReceiver($order);

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertStringContainsString('没有需要重试的分账接收方', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionMerchantId(): void
    {
        $this->cleanReceiverData();

        // 创建两个不同的商户
        $merchant1 = $this->createMerchant('1111111111');
        $merchant2 = $this->createMerchant('2222222222');

        // 为merchant1创建接收方
        $order1 = $this->createOrder($merchant1);
        $receiver1 = $this->createReceiver($order1);

        // 为merchant2创建接收方
        $order2 = $this->createOrder($merchant2);
        $receiver2 = $this->createReceiver($order2);

        // 只处理merchant1的接收方
        $this->commandTester->execute(['--merchant-id' => '1111111111', '--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionRetryInterval(): void
    {
        $this->cleanReceiverData();

        $merchant = $this->createMerchant();
        $order = $this->createOrder($merchant);
        // 创建一个接收方，设置下次重试时间为未来，不应该被重试
        $receiver = new ProfitShareReceiver();
        $receiver->setOrder($order);
        $receiver->setSequence(1);
        $receiver->setType('MERCHANT_ID');
        $receiver->setAccount('test_account');
        $receiver->setAmount(100);
        $receiver->setDescription('测试分账接收方');
        $receiver->setResult(ProfitShareReceiverResult::FAILED);
        $receiver->setRetryCount(1);
        // 设置下次重试时间为未来，确保不会被重试
        $receiver->setNextRetryAt(new \DateTimeImmutable('+50 minutes'));

        $this->persistAndFlush($receiver);

        $this->commandTester->execute(['--retry-interval' => 60]); // 60分钟间隔

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('跳过处理数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithMultipleReceivers(): void
    {
        $this->cleanReceiverData();

        $merchant = $this->createMerchant();
        $order = $this->createOrder($merchant);

        // 创建多个接收方
        $receiver1 = $this->createReceiver($order);
        $receiver1->setAccount('account1');
        $this->persistAndFlush($receiver1);

        $receiver2 = $this->createReceiver($order);
        $receiver2->setAccount('account2');
        $this->persistAndFlush($receiver2);

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertStringContainsString('总接收方数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        $this->receiverRepository = self::getService(ProfitShareReceiverRepository::class);

        /** @var ProfitShareRetryCommand $command */
        $command = self::getService(ProfitShareRetryCommand::class);

        $application = new Application();
        $application->addCommand($command);
        $this->commandTester = new CommandTester($command);
    }
}
