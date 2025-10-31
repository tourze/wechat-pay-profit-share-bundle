<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReceiver;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareReceiverResult;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareReceiverRepository;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareService;
use WechatPayBundle\Entity\Merchant;

#[AsCommand(
    name: 'wechat:profit-share:retry',
    description: '重试失败的微信支付分账接收方'
)]
#[WithMonologChannel(channel: 'wechat_pay_profit_share')]
class ProfitShareRetryCommand extends Command
{
    public function __construct(
        private readonly ProfitShareReceiverRepository $receiverRepository,
        private readonly ProfitShareService $profitShareService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '只模拟执行，不实际重试')
            ->addOption('max-retry', null, InputOption::VALUE_OPTIONAL, '最大重试次数', 3)
            ->addOption('retry-interval', null, InputOption::VALUE_OPTIONAL, '重试间隔（分钟）', 30)
            ->addOption('merchant-id', null, InputOption::VALUE_OPTIONAL, '指定商户ID，不指定则处理所有商户')
            ->setHelp('
此命令用于重试失败的分账接收方。

使用示例：
  # 重试所有失败的接收方
  php bin/console wechat:profit-share:retry

  # 模拟执行，不实际重试
  php bin/console wechat:profit-share:retry --dry-run

  # 设置最大重试次数为5次
  php bin/console wechat:profit-share:retry --max-retry=5

  # 设置重试间隔为60分钟
  php bin/console wechat:profit-share:retry --retry-interval=60

  # 指定商户
  php bin/console wechat:profit-share:retry --merchant-id=123
            ')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRunOption = $input->getOption('dry-run');
        $dryRun = is_bool($dryRunOption) ? $dryRunOption : false;

        $maxRetryOption = $input->getOption('max-retry');
        $maxRetry = is_int($maxRetryOption) || is_string($maxRetryOption) ? (int) $maxRetryOption : 3;

        $retryIntervalOption = $input->getOption('retry-interval');
        $retryInterval = is_int($retryIntervalOption) || is_string($retryIntervalOption) ? (int) $retryIntervalOption : 30;

        $merchantIdOption = $input->getOption('merchant-id');
        $merchantId = is_string($merchantIdOption) ? $merchantIdOption : null;

        $io->title('微信支付分账重试处理');

        if ($dryRun) {
            $io->warning('当前为模拟执行模式，不会实际重试分账');
        }

        try {
            // 查询需要重试的接收方
            $failedReceivers = $this->getFailedReceivers($merchantId);

            if (0 === count($failedReceivers)) {
                $io->success('没有需要重试的分账接收方');

                return Command::SUCCESS;
            }

            $io->section('开始重试失败的接收方');
            $io->progressStart(count($failedReceivers));

            $retrySuccessCount = 0;
            $retryFailedCount = 0;
            $skipCount = 0;
            $finallyFailedCount = 0;

            $now = new \DateTimeImmutable();
            $retryThreshold = new \DateTimeImmutable("-{$retryInterval} minutes");

            foreach ($failedReceivers as $receiver) {
                $result = $this->processReceiver(
                    $receiver,
                    $dryRun,
                    $maxRetry,
                    $retryInterval,
                    $retryThreshold,
                    $now
                );

                match ($result['status']) {
                    'max_retry_reached' => ++$finallyFailedCount,
                    'skipped' => ++$skipCount,
                    'success' => ++$retrySuccessCount,
                    'failed' => ++$retryFailedCount,
                    default => null,
                };

                $io->progressAdvance();
            }

            $io->progressFinish();

            // 输出统计信息
            $io->section('重试结果统计');
            $io->table(
                ['类型', '数量'],
                [
                    ['总接收方数', count($failedReceivers)],
                    ['重试成功数', $retrySuccessCount],
                    ['重试失败数', $retryFailedCount],
                    ['跳过处理数', $skipCount],
                    ['最终失败数', $finallyFailedCount],
                ]
            );

            if ($retryFailedCount > 0) {
                $io->warning("发现 {$retryFailedCount} 个接收方重试失败，将在下次继续尝试");
            }

            if ($finallyFailedCount > 0) {
                $io->warning("发现 {$finallyFailedCount} 个接收方已达到最大重试次数，需要人工处理");
            }

            $io->success('分账重试处理完成');
        } catch (\Throwable $exception) {
            $this->logger->error('分账重试命令执行失败', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $io->error('重试失败：' . $exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * 处理单个接收方
     *
     * @return array{status: string}
     */
    private function processReceiver(
        ProfitShareReceiver $receiver,
        bool $dryRun,
        int $maxRetry,
        int $retryInterval,
        \DateTimeImmutable $retryThreshold,
        \DateTimeImmutable $now
    ): array {
        try {
            // 检查是否达到最大重试次数
            if ($receiver->getRetryCount() >= $maxRetry) {
                return $this->handleMaxRetryReached($receiver, $dryRun, $maxRetry);
            }

            // 检查是否应该跳过
            if ($this->shouldSkipReceiver($receiver, $now, $retryThreshold)) {
                return ['status' => 'skipped'];
            }

            // 验证订单和商户信息
            $order = $receiver->getOrder();
            if (null === $order) {
                $this->logger->error('分账接收方缺少订单信息', [
                    'receiver_id' => $receiver->getId(),
                    'account' => $receiver->getAccount(),
                ]);

                return ['status' => 'failed'];
            }

            $merchant = $order->getMerchant();
            if (null === $merchant) {
                $this->logger->error('分账订单缺少商户信息', [
                    'order_id' => $order->getId(),
                    'receiver_id' => $receiver->getId(),
                ]);

                return ['status' => 'failed'];
            }

            // 执行重试
            return $this->retryReceiver($receiver, $order, $merchant, $dryRun, $retryInterval);
        } catch (\Throwable $exception) {
            $this->logger->error('重试分账接收方失败', [
                'receiver_id' => $receiver->getId(),
                'account' => $receiver->getAccount(),
                'error' => $exception->getMessage(),
            ]);

            if ($dryRun === false) {
                $receiver->setRetryCount($receiver->getRetryCount() + 1);
                $receiver->setNextRetryAt(new \DateTimeImmutable("+{$retryInterval} minutes"));
                $this->entityManager->persist($receiver);
                $this->entityManager->flush();
            }

            return ['status' => 'failed'];
        }
    }

    /**
     * 判断是否应跳过接收方
     */
    private function shouldSkipReceiver(
        ProfitShareReceiver $receiver,
        \DateTimeImmutable $now,
        \DateTimeImmutable $retryThreshold
    ): bool {
        // 检查是否到了重试时间
        $nextRetryAt = $receiver->getNextRetryAt();
        if ($nextRetryAt !== null && $nextRetryAt > $now) {
            return true;
        }

        // 检查距离上次重试是否足够间隔
        $updatedAt = $receiver->getUpdatedAt();
        if ($updatedAt !== null && $updatedAt > $retryThreshold) {
            return true;
        }

        return false;
    }

    /**
     * 处理达到最大重试次数的情况
     *
     * @return array{status: string}
     */
    private function handleMaxRetryReached(
        ProfitShareReceiver $receiver,
        bool $dryRun,
        int $maxRetry
    ): array {
        $this->logger->warning('分账接收方达到最大重试次数，标记为最终失败', [
            'receiver_id' => $receiver->getId(),
            'account' => $receiver->getAccount(),
            'amount' => $receiver->getAmount(),
            'retry_count' => $receiver->getRetryCount(),
            'max_retry' => $maxRetry,
        ]);

        if ($dryRun === false) {
            $receiver->setFinallyFailed(true);
            $this->entityManager->persist($receiver);
            $this->entityManager->flush();
        }

        return ['status' => 'max_retry_reached'];
    }

    /**
     * 执行接收方重试
     *
     * @return array{status: string}
     */
    private function retryReceiver(
        ProfitShareReceiver $receiver,
        ProfitShareOrder $order,
        Merchant $merchant,
        bool $dryRun,
        int $retryInterval
    ): array {
        if ($dryRun) {
            return $this->simulateRetry($receiver);
        }

        return $this->executeActualRetry($receiver, $order, $merchant, $retryInterval);
    }

    /**
     * 模拟重试执行
     *
     * @return array{status: string}
     */
    private function simulateRetry(ProfitShareReceiver $receiver): array
    {
        $this->logger->info('模拟分账接收方重试', [
            'receiver_id' => $receiver->getId(),
            'account' => $receiver->getAccount(),
            'amount' => $receiver->getAmount(),
            'retry_count' => $receiver->getRetryCount() + 1,
        ]);

        return ['status' => 'success'];
    }

    /**
     * 执行实际重试
     *
     * @return array{status: string}
     */
    private function executeActualRetry(
        ProfitShareReceiver $receiver,
        ProfitShareOrder $order,
        Merchant $merchant,
        int $retryInterval
    ): array {
        // 重新查询订单状态（这可能会触发重新分账）
        $updatedOrder = $this->profitShareService->queryProfitShareOrder(
            $merchant,
            $order->getSubMchId(),
            $order->getOutOrderNo(),
            $order->getTransactionId()
        );

        // 更新接收方重试信息
        $receiver->setRetryCount($receiver->getRetryCount() + 1);

        // 检查重试是否成功
        $currentReceiver = $this->findMatchingReceiver($receiver, $updatedOrder);
        $isSuccess = $currentReceiver instanceof ProfitShareReceiver
            && ProfitShareReceiverResult::SUCCESS === $currentReceiver->getResult();

        if ($isSuccess) {
            return $this->handleRetrySuccess($receiver);
        }

        return $this->handleRetryFailure($receiver, $retryInterval);
    }

    /**
     * 查找匹配的接收方
     */
    private function findMatchingReceiver(
        ProfitShareReceiver $receiver,
        ProfitShareOrder $updatedOrder
    ): ?ProfitShareReceiver {
        foreach ($updatedOrder->getReceivers() as $r) {
            if ($r->getAccount() === $receiver->getAccount()
                && $r->getAmount() === $receiver->getAmount()) {
                return $r;
            }
        }

        return null;
    }

    /**
     * 处理重试成功
     *
     * @return array{status: string}
     */
    private function handleRetrySuccess(ProfitShareReceiver $receiver): array
    {
        $this->logger->info('分账接收方重试成功', [
            'receiver_id' => $receiver->getId(),
            'account' => $receiver->getAccount(),
            'amount' => $receiver->getAmount(),
            'retry_count' => $receiver->getRetryCount(),
        ]);

        $this->entityManager->persist($receiver);
        $this->entityManager->flush();

        return ['status' => 'success'];
    }

    /**
     * 处理重试失败
     *
     * @return array{status: string}
     */
    private function handleRetryFailure(ProfitShareReceiver $receiver, int $retryInterval): array
    {
        $receiver->setNextRetryAt(new \DateTimeImmutable("+{$retryInterval} minutes"));
        $nextRetryAt = $receiver->getNextRetryAt();
        $this->logger->warning('分账接收方重试失败', [
            'receiver_id' => $receiver->getId(),
            'account' => $receiver->getAccount(),
            'amount' => $receiver->getAmount(),
            'retry_count' => $receiver->getRetryCount(),
            'next_retry_at' => $nextRetryAt !== null ? $nextRetryAt->format('Y-m-d H:i:s') : null,
        ]);

        $this->entityManager->persist($receiver);
        $this->entityManager->flush();

        return ['status' => 'failed'];
    }

    /**
     * 获取需要重试的接收方
     *
     * @return array<ProfitShareReceiver>
     */
    private function getFailedReceivers(?string $merchantId): array
    {
        $qb = $this->receiverRepository->createQueryBuilder('r')
            ->innerJoin('r.order', 'o')
            ->innerJoin('o.merchant', 'm')
            ->where('r.result IN (:failedStates)')
            ->andWhere('r.finallyFailed = false')
            ->setParameter('failedStates', [
                ProfitShareReceiverResult::FAILED,
                ProfitShareReceiverResult::PENDING, // 处理中超过时间的也需要重试
            ])
        ;

        if ($merchantId !== null) {
            $qb->andWhere('m.id = :merchantId')
                ->setParameter('merchantId', $merchantId)
            ;
        }

        // 只查询最近7天内的记录
        $since = new \DateTimeImmutable('-7 days');
        $qb->andWhere('r.createdAt >= :since')
            ->setParameter('since', $since)
        ;

        /** @var array<ProfitShareReceiver> */
        return $qb->getQuery()->getResult();
    }
}
