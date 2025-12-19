<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Command;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOrderState;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOrderRepository;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareService;

#[AsCommand(
    name: 'wechat:profit-share:sync',
    description: '同步微信支付分账订单状态'
)]
#[WithMonologChannel(channel: 'wechat_pay_profit_share')]
final class ProfitShareSyncCommand extends Command
{
    public function __construct(
        private readonly ProfitShareOrderRepository $orderRepository,
        private readonly ProfitShareService $profitShareService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '只模拟执行，不实际更新数据')
            ->addOption('timeout-hours', null, InputOption::VALUE_OPTIONAL, '订单超时时间（小时）', 24)
            ->setHelp('
此命令用于同步处理中状态的分账订单状态。

使用示例：
  # 执行状态同步
  php bin/console wechat:profit-share:sync

  # 模拟执行，不实际更新数据
  php bin/console wechat:profit-share:sync --dry-run

  # 设置超时时间为48小时
  php bin/console wechat:profit-share:sync --timeout-hours=48
            ')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRunOption = $input->getOption('dry-run');
        $dryRun = is_bool($dryRunOption) && $dryRunOption;

        $timeoutHoursOption = $input->getOption('timeout-hours');
        $timeoutHours = is_int($timeoutHoursOption) ? $timeoutHoursOption : (is_string($timeoutHoursOption) ? (int) $timeoutHoursOption : 24);

        $io->title('微信支付分账状态同步');

        if ($dryRun) {
            $io->warning('当前为模拟执行模式，不会实际更新数据');
        }

        try {
            $processingOrders = $this->orderRepository->findBy([
                'state' => ProfitShareOrderState::PROCESSING,
            ]);

            if (0 === count($processingOrders)) {
                $io->success('没有需要同步的订单');

                return Command::SUCCESS;
            }

            $statistics = $this->syncOrdersInBatch($processingOrders, $timeoutHours, $dryRun, $io);
            $this->displayStatistics($io, $processingOrders, $statistics);
            $io->success('分账状态同步完成');
        } catch (\Throwable $exception) {
            $this->logger->error('分账状态同步命令执行失败', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $io->error('同步失败：' . $exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * 批量同步订单
     *
     * @param array<ProfitShareOrder> $orders
     *
     * @return array{updated: int, timeout: int, error: int}
     */
    private function syncOrdersInBatch(array $orders, int $timeoutHours, bool $dryRun, SymfonyStyle $io): array
    {
        $io->section('开始同步处理中的订单');
        $io->progressStart(count($orders));

        $statistics = ['updated' => 0, 'timeout' => 0, 'error' => 0];
        $timeoutThreshold = new \DateTimeImmutable("-{$timeoutHours} hours");

        foreach ($orders as $order) {
            try {
                $processResult = $this->processSingleOrder($order, $timeoutThreshold, $timeoutHours, $dryRun);
                $statistics['updated'] += $processResult['updated'];
                $statistics['timeout'] += $processResult['timeout'];
                $statistics['error'] += $processResult['error'];
            } catch (\Throwable $exception) {
                ++$statistics['error'];
                $this->logger->error('同步分账订单状态失败', [
                    'order_id' => $order->getId(),
                    'out_order_no' => $order->getOutOrderNo(),
                    'error' => $exception->getMessage(),
                ]);
            }

            $io->progressAdvance();
        }

        $io->progressFinish();

        return $statistics;
    }

    /**
     * 显示统计信息
     *
     * @param array<ProfitShareOrder> $orders
     * @param array{updated: int, timeout: int, error: int} $statistics
     */
    private function displayStatistics(SymfonyStyle $io, array $orders, array $statistics): void
    {
        $io->section('同步结果统计');
        $io->table(
            ['类型', '数量'],
            [
                ['总订单数', count($orders)],
                ['状态更新数', $statistics['updated']],
                ['超时订单数', $statistics['timeout']],
                ['错误订单数', $statistics['error']],
            ]
        );

        if ($statistics['error'] > 0) {
            $io->warning("发现 {$statistics['error']} 个订单处理失败，请查看日志");
        }

        if ($statistics['timeout'] > 0) {
            $io->warning("发现 {$statistics['timeout']} 个超时订单，建议人工处理");
        }
    }

    /**
     * 处理单个订单
     *
     * @return array{updated: int, timeout: int, error: int}
     */
    private function processSingleOrder(
        ProfitShareOrder $order,
        \DateTimeImmutable $timeoutThreshold,
        int $timeoutHours,
        bool $dryRun,
    ): array {
        $result = ['updated' => 0, 'timeout' => 0, 'error' => 0];

        // 检查订单是否超时
        $createdAt = $order->getCreatedAt();
        if (null !== $createdAt && $createdAt < $timeoutThreshold) {
            $result['timeout'] = 1;
            $this->logger->warning('分账订单超时', [
                'order_id' => $order->getId(),
                'out_order_no' => $order->getOutOrderNo(),
                'created_at' => $createdAt->format('Y-m-d H:i:s'),
                'timeout_hours' => $timeoutHours,
            ]);

            return $result;
        }

        // 获取商户信息
        $merchant = $order->getMerchant();
        if (null === $merchant) {
            $this->logger->error('分账订单缺少商户信息', [
                'order_id' => $order->getId(),
                'out_order_no' => $order->getOutOrderNo(),
            ]);
            $result['error'] = 1;

            return $result;
        }

        // 查询微信支付状态
        $updatedOrder = $this->profitShareService->queryProfitShareOrder(
            $merchant,
            $order->getSubMchId(),
            $order->getOutOrderNo(),
            $order->getTransactionId()
        );

        // 检查状态是否有变化
        if ($updatedOrder->getState() !== $order->getState()) {
            $result['updated'] = 1;
            $this->logger->info('分账订单状态已更新', [
                'order_id' => $order->getId(),
                'out_order_no' => $order->getOutOrderNo(),
                'old_state' => $order->getState()->value,
                'new_state' => $updatedOrder->getState()->value,
            ]);

            if (false === $dryRun) {
                $this->orderRepository->save($updatedOrder);
            }
        }

        return $result;
    }
}
