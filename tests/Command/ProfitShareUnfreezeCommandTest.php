<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Command;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\WechatPayProfitShareBundle\Command\ProfitShareUnfreezeCommand;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOrderState;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOrderRepository;
use WechatPayBundle\Entity\Merchant;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareUnfreezeCommand::class)]
final class ProfitShareUnfreezeCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    private ProfitShareOrderRepository $orderRepository;

    public function testExecuteWithNoOrders(): void
    {
        // 清理数据库，确保没有待解冻的订单
        $this->cleanupTestData();

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('没有需要解冻的订单', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    private function createMerchant(?string $mchId = null): Merchant
    {
        $merchant = new Merchant();
        $merchant->setMchId($mchId ?? uniqid('mch_', true));
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setApiKeyV3('test_api_key_v3_' . uniqid());
        $merchant->setPemKey('test_pem_key_' . uniqid());
        $merchant->setValid(true);

        $this->persistAndFlush($merchant);

        return $merchant;
    }

    private function createOrder(?Merchant $merchant = null): ProfitShareOrder
    {
        $order = new ProfitShareOrder();
        $order->setSubMchId('1234567890');
        $order->setTransactionId('TX' . uniqid());
        $order->setOutOrderNo('ORDER_' . uniqid());
        $order->setUnfreezeUnsplit(false); // 未解冻
        $order->setState(ProfitShareOrderState::FINISHED); // 已完成状态
        $order->setWechatFinishedAt(new \DateTimeImmutable('-50 hours')); // 50小时前完成，符合48小时解冻条件
        $order->setCreateTime(new \DateTimeImmutable('-1 day')); // 设置创建时间在30天内
        if (null !== $merchant) {
            $order->setMerchant($merchant);
        }

        $this->persistAndFlush($order);

        return $order;
    }

    private function createAlreadyUnfrozenOrder(?Merchant $merchant = null): ProfitShareOrder
    {
        $order = $this->createOrder($merchant);
        $order->setUnfreezeUnsplit(true); // 已解冻
        $this->persistAndFlush($order);

        return $order;
    }

    public function testExecuteWithDryRun(): void
    {
        $this->cleanupTestData();
        $merchant = $this->createMerchant();
        $order = $this->createOrder($merchant);

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertStringContainsString('解冻成功数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithAlreadyUnfrozenOrder(): void
    {
        $this->cleanupTestData();
        $merchant = $this->createMerchant();
        $order = $this->createAlreadyUnfrozenOrder($merchant);

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('没有需要解冻的订单', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithSuccessfulUnfreeze(): void
    {
        $this->cleanupTestData();
        $merchant = $this->createMerchant();
        $order = $this->createOrder($merchant);

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertStringContainsString('解冻成功数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithMissingMerchant(): void
    {
        $this->cleanupTestData();
        $order = $this->createOrder(); // 没有设置merchant

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertStringContainsString('没有需要解冻的订单', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithForceUnfreeze(): void
    {
        $this->cleanupTestData();
        $merchant = $this->createMerchant();
        $order = $this->createOrder($merchant);

        $this->commandTester->execute(['--force-unfreeze' => true, '--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertStringContainsString('总订单数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithSpecificMerchant(): void
    {
        $this->cleanupTestData();
        $merchant1 = $this->createMerchant('1111111111');
        $merchant2 = $this->createMerchant('2222222222');

        // 为merchant1创建订单
        $order1 = $this->createOrder($merchant1);

        // 为merchant2创建订单
        $order2 = $this->createOrder($merchant2);

        // 只处理merchant1的订单
        $this->commandTester->execute(['--merchant-id' => '1111111111', '--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithCustomUnfreezeHours(): void
    {
        $this->cleanupTestData();
        $merchant = $this->createMerchant();
        $order = $this->createOrder($merchant);
        // 设置微信完成时间为25小时前，对于24小时的解冻时间来说应该被处理
        $order->setWechatFinishedAt(new \DateTimeImmutable('-25 hours'));
        $this->persistAndFlush($order);

        $this->commandTester->execute(['--unfreeze-hours' => 24, '--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertStringContainsString('解冻成功数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithRecentOrder(): void
    {
        $this->cleanupTestData();
        $merchant = $this->createMerchant();
        $order = $this->createOrder($merchant);
        // 设置微信完成时间为1小时前，对于默认的48小时解冻时间来说不应该被处理
        $order->setWechatFinishedAt(new \DateTimeImmutable('-1 hour'));
        $this->persistAndFlush($order);

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('没有需要解冻的订单', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionDryRun(): void
    {
        $this->cleanupTestData();
        $merchant = $this->createMerchant();
        $order = $this->createOrder($merchant);

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertStringContainsString('解冻成功数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionUnfreezeHours(): void
    {
        $this->cleanupTestData();
        $merchant = $this->createMerchant();
        $order = $this->createOrder($merchant);
        // 设置微信完成时间为3小时前，对于2小时的解冻时间来说应该被处理
        $order->setWechatFinishedAt(new \DateTimeImmutable('-3 hours'));
        // 设置创建时间在30天内
        $order->setCreateTime(new \DateTimeImmutable('-1 day'));
        $this->persistAndFlush($order);

        $this->commandTester->execute(['--unfreeze-hours' => 2, '--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertStringContainsString('解冻成功数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionForceUnfreeze(): void
    {
        $this->cleanupTestData();
        $merchant = $this->createMerchant();
        $order = $this->createOrder($merchant);

        $this->commandTester->execute(['--force-unfreeze' => true, '--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertStringContainsString('总订单数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionMerchantId(): void
    {
        $this->cleanupTestData();
        $merchant1 = $this->createMerchant('1111111111');
        $merchant2 = $this->createMerchant('2222222222');

        // 为merchant1创建订单
        $order1 = $this->createOrder($merchant1);

        // 为merchant2创建订单
        $order2 = $this->createOrder($merchant2);

        // 只处理merchant1的订单
        $this->commandTester->execute(['--merchant-id' => '1111111111', '--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithMultipleOrders(): void
    {
        $this->cleanupTestData();
        $merchant = $this->createMerchant();

        // 创建多个订单
        $order1 = $this->createOrder($merchant);
        $order1->setOutOrderNo('ORDER1');

        $order2 = $this->createAlreadyUnfrozenOrder($merchant);
        $order2->setOutOrderNo('ORDER2');

        $order3 = $this->createOrder($merchant);
        $order3->setOutOrderNo('ORDER3');

        $this->persistAndFlush($order1);
        $this->persistAndFlush($order2);
        $this->persistAndFlush($order3);

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertStringContainsString('总订单数', $output);
        $this->assertStringContainsString('跳过处理数', $output); // 已解冻的订单会被跳过
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    /**
     * 清理测试数据，确保测试环境干净
     */
    private function cleanupTestData(): void
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        if ($em instanceof EntityManagerInterface) {
            // 清理分账订单数据
            $em->createQuery('DELETE FROM Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder')->execute();
            // 清理商户数据
            $em->createQuery('DELETE FROM WechatPayBundle\Entity\Merchant')->execute();
        }
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        $this->orderRepository = self::getService(ProfitShareOrderRepository::class);

        /** @var ProfitShareUnfreezeCommand $command */
        $command = self::getService(ProfitShareUnfreezeCommand::class);

        $application = new Application();
        $application->addCommand($command);
        $this->commandTester = new CommandTester($command);
    }
}
