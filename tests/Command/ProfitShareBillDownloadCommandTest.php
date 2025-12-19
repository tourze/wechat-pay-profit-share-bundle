<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\WechatPayProfitShareBundle\Command\ProfitShareBillDownloadCommand;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareBillTask;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareBillStatus;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareBillTaskRepository;
use WechatPayBundle\DataFixtures\MerchantFixtures;
use WechatPayBundle\Entity\Merchant;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareBillDownloadCommand::class)]
final class ProfitShareBillDownloadCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    /**
     * 清理 fixtures 加载的任务数据
     */
    private function cleanTaskData(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\WechatPayProfitShareBundle\Entity\ProfitShareBillTask')->execute();
    }

    public function testExecuteWithNoTasks(): void
    {
        $this->cleanTaskData();

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('没有需要下载的账单', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    private function createTask(): ProfitShareBillTask
    {
        $task = new ProfitShareBillTask();
        $task->setSubMchId('1234567890');
        $task->setBillDate(new \DateTimeImmutable('-1 day'));
        $task->setStatus(ProfitShareBillStatus::READY);
        // 使用随机ID避免唯一约束冲突
        $task->setTarType(uniqid('test_', true));

        return $task;
    }

    private function createTaskWithMerchant(): ProfitShareBillTask
    {
        $merchant = $this->createMerchant();
        $task = new ProfitShareBillTask();
        $task->setSubMchId('1234567890');
        $task->setBillDate(new \DateTimeImmutable('-1 day'));
        $task->setStatus(ProfitShareBillStatus::READY);
        $task->setMerchant($merchant);
        // 使用随机ID避免唯一约束冲突
        $task->setTarType(uniqid('merchant_', true));

        return $task;
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

        if ('0987654321' === $mchId) {
            $merchant = $merchantRepository->findOneBy(['mchId' => '0987654321']);
            if (null !== $merchant) {
                return $merchant;
            }
        }

        // 如果指定了其他mchId或找不到现有商户，生成一个新的唯一商户
        $newMchId = '5555555555'; // 使用一个不冲突的ID

        $merchant = new Merchant();
        $merchant->setMchId($newMchId);
        $merchant->setApiKey('test_api_key_' . $newMchId);
        $merchant->setApiKeyV3('test_api_key_v3_' . $newMchId);
        $merchant->setPemKey('test_pem_key_' . $newMchId);
        $merchant->setValid(true);

        $this->persistAndFlush($merchant);

        return $merchant;
    }

    private function createExpiredTask(): ProfitShareBillTask
    {
        $task = new ProfitShareBillTask();
        $task->setSubMchId('1234567890');
        $task->setBillDate(new \DateTimeImmutable('-8 days'));
        $task->setStatus(ProfitShareBillStatus::READY);
        // 使用随机ID避免唯一约束冲突
        $task->setTarType(uniqid('expired_', true));

        return $task;
    }

    public function testExecuteWithExpiredTask(): void
    {
        $this->cleanTaskData();

        $task = $this->createExpiredTask();
        $this->persistAndFlush($task);

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('错误任务数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithTaskWithoutMerchant(): void
    {
        $this->cleanTaskData();

        $task = $this->createTask();
        $this->persistAndFlush($task);

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('错误任务数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionDryRun(): void
    {
        $this->cleanTaskData();

        $task = $this->createTaskWithMerchant();
        $this->persistAndFlush($task);

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionExpireDays(): void
    {
        $this->cleanTaskData();

        $task = $this->createExpiredTask();
        $this->persistAndFlush($task);

        $this->commandTester->execute(['--expire-days' => 10]);

        $output = $this->commandTester->getDisplay();
        // 由于过期时间设置为10天，8天的任务不应该被当作过期，但仍然会尝试处理
        $this->assertStringContainsString('错误任务数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionDownloadPath(): void
    {
        $this->cleanTaskData();

        $task = $this->createTaskWithMerchant();
        $this->persistAndFlush($task);

        $this->commandTester->execute(['--download-path' => '/custom/path', '--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionMerchantId(): void
    {
        $this->cleanTaskData();

        $task = $this->createTask();
        $this->persistAndFlush($task);

        $this->commandTester->execute(['--merchant-id' => '9999999999']);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('没有需要下载的账单', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithMultipleTasks(): void
    {
        $this->cleanTaskData();

        // 使用 fixtures 中的两个不同商户
        $merchant1 = $this->createMerchant('1234567890'); // TEST_MERCHANT_REFERENCE
        $merchant2 = $this->createMerchant('0987654321'); // DEMO_MERCHANT_REFERENCE

        // 创建多个任务
        $task1 = new ProfitShareBillTask();
        $task1->setSubMchId('1234567890');
        $task1->setBillDate(new \DateTimeImmutable('-1 day'));
        $task1->setStatus(ProfitShareBillStatus::READY);
        $task1->setMerchant($merchant1);
        $task1->setTarType(uniqid('multi1_', true));

        $task2 = new ProfitShareBillTask();
        $task2->setSubMchId('0987654321');
        $task2->setBillDate(new \DateTimeImmutable('-1 day'));
        $task2->setStatus(ProfitShareBillStatus::READY);
        $task2->setMerchant($merchant2);
        $task2->setTarType(uniqid('multi2_', true));

        $this->persistAndFlush($task1);
        $this->persistAndFlush($task2);

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('模拟执行模式', $output);
        $this->assertStringContainsString('总任务数', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        /** @var ProfitShareBillDownloadCommand $command */
        $command = self::getService(ProfitShareBillDownloadCommand::class);

        $application = new Application();
        $application->addCommand($command);
        $this->commandTester = new CommandTester($command);
    }
}
