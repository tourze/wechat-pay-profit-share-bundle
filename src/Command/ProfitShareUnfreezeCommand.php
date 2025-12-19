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
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareUnfreezeRequest;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareService;

#[AsCommand(
    name: 'wechat:profit-share:unfreeze',
    description: '监控并执行微信支付分账资金解冻'
)]
#[WithMonologChannel(channel: 'wechat_pay_profit_share')]
final class ProfitShareUnfreezeCommand extends Command
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
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '只模拟执行，不实际解冻资金')
            ->addOption('unfreeze-hours', null, InputOption::VALUE_OPTIONAL, '订单完成后多少小时执行解冻', 48)
            ->addOption('force-unfreeze', null, InputOption::VALUE_NONE, '强制解冻所有符合条件的订单')
            ->addOption('merchant-id', null, InputOption::VALUE_OPTIONAL, '指定商户ID，不指定则处理所有商户')
            ->setHelp('
此命令用于监控已完成但未解冻的分账订单，自动执行资金解冻操作。

使用示例：
  # 执行资金解冻监控
  php bin/console wechat:profit-share:unfreeze

  # 模拟执行，不实际解冻
  php bin/console wechat:profit-share:unfreeze --dry-run

  # 设置完成后24小时执行解冻
  php bin/console wechat:profit-share:unfreeze --unfreeze-hours=24

  # 强制解冻所有符合条件的订单
  php bin/console wechat:profit-share:unfreeze --force-unfreeze

  # 指定商户
  php bin/console wechat:profit-share:unfreeze --merchant-id=123
            ')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $dryRunOption = $input->getOption('dry-run');
        $dryRun = is_bool($dryRunOption) && $dryRunOption;

        $unfreezeHoursOption = $input->getOption('unfreeze-hours');
        $unfreezeHours = is_int($unfreezeHoursOption) ? $unfreezeHoursOption : (is_string($unfreezeHoursOption) ? (int) $unfreezeHoursOption : 48);

        $forceUnfreezeOption = $input->getOption('force-unfreeze');
        $forceUnfreeze = is_bool($forceUnfreezeOption) && $forceUnfreezeOption;

        $merchantIdOption = $input->getOption('merchant-id');
        $merchantId = is_string($merchantIdOption) ? $merchantIdOption : null;

        $io->title('微信支付分账资金解冻监控');

        if ($dryRun) {
            $io->warning('当前为模拟执行模式，不会实际解冻资金');
        }

        try {
            // 查询需要解冻的订单
            $ordersToUnfreeze = $this->getOrdersToUnfreeze($merchantId, $unfreezeHours, $forceUnfreeze);

            if (0 === count($ordersToUnfreeze)) {
                $io->success('没有需要解冻的订单');

                return Command::SUCCESS;
            }

            $statistics = $this->unfreezeOrdersInBatch($ordersToUnfreeze, $dryRun, $io);
            $this->displayUnfreezeStatistics($io, $ordersToUnfreeze, $statistics);
            $io->success('分账资金解冻处理完成');
        } catch (\Throwable $exception) {
            $this->logger->error('分账资金解冻命令执行失败', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $io->error('解冻失败：' . $exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * 批量解冻订单
     *
     * @param array<ProfitShareOrder> $orders
     *
     * @return array{success: int, failed: int, skip: int}
     */
    private function unfreezeOrdersInBatch(array $orders, bool $dryRun, SymfonyStyle $io): array
    {
        $io->section('开始执行资金解冻');
        $io->progressStart(count($orders));

        $statistics = ['success' => 0, 'failed' => 0, 'skip' => 0];

        foreach ($orders as $order) {
            try {
                $unfreezeResult = $this->processSingleUnfreeze($order, $dryRun);
                $statistics['success'] += $unfreezeResult['success'];
                $statistics['failed'] += $unfreezeResult['failed'];
                $statistics['skip'] += $unfreezeResult['skip'];
            } catch (\Throwable $exception) {
                ++$statistics['failed'];
                $this->logger->error('分账资金解冻失败', [
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
     * 显示解冻统计信息
     *
     * @param array<ProfitShareOrder> $orders
     * @param array{success: int, failed: int, skip: int} $statistics
     */
    private function displayUnfreezeStatistics(SymfonyStyle $io, array $orders, array $statistics): void
    {
        $io->section('解冻结果统计');
        $io->table(
            ['类型', '数量'],
            [
                ['总订单数', count($orders)],
                ['解冻成功数', $statistics['success']],
                ['解冻失败数', $statistics['failed']],
                ['跳过处理数', $statistics['skip']],
            ]
        );

        if ($statistics['failed'] > 0) {
            $io->warning("发现 {$statistics['failed']} 个订单解冻失败，请查看日志");
        }
    }

    /**
     * 处理单个订单的解冻
     *
     * @return array{success: int, failed: int, skip: int}
     */
    private function processSingleUnfreeze(ProfitShareOrder $order, bool $dryRun): array
    {
        $result = ['success' => 0, 'failed' => 0, 'skip' => 0];

        // 检查是否已经解冻
        if ($order->isUnfreezeUnsplit()) {
            $result['skip'] = 1;
            $this->logger->info('订单已解冻，跳过处理', [
                'order_id' => $order->getId(),
                'out_order_no' => $order->getOutOrderNo(),
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
            $result['failed'] = 1;

            return $result;
        }

        if (false === $dryRun) {
            // 创建解冻请求
            $unfreezeRequest = new ProfitShareUnfreezeRequest();
            $unfreezeRequest->setSubMchId($order->getSubMchId());
            $unfreezeRequest->setTransactionId($order->getTransactionId());
            $unfreezeRequest->setOutOrderNo($order->getOutOrderNo());
            $unfreezeRequest->setUnfreezeUnsplit(true);

            // 执行解冻操作
            $unfrozenOrder = $this->profitShareService->unfreezeRemainingAmount($merchant, $unfreezeRequest);

            if ($unfrozenOrder->isUnfreezeUnsplit()) {
                $result['success'] = 1;
                $this->logger->info('分账资金解冻成功', [
                    'order_id' => $order->getId(),
                    'out_order_no' => $order->getOutOrderNo(),
                    'sub_mchid' => $order->getSubMchId(),
                    'transaction_id' => $order->getTransactionId(),
                ]);
            } else {
                $result['failed'] = 1;
                $this->logger->warning('分账资金解冻状态未知', [
                    'order_id' => $order->getId(),
                    'out_order_no' => $order->getOutOrderNo(),
                ]);
            }
        } else {
            // 模拟解冻成功
            $result['success'] = 1;
            $this->logger->info('模拟分账资金解冻', [
                'order_id' => $order->getId(),
                'out_order_no' => $order->getOutOrderNo(),
                'sub_mchid' => $order->getSubMchId(),
            ]);
        }

        return $result;
    }

    /**
     * 获取需要解冻的订单
     *
     * @return array<ProfitShareOrder>
     */
    private function getOrdersToUnfreeze(?string $merchantId, int $unfreezeHours, bool $forceUnfreeze): array
    {
        $qb = $this->orderRepository->createQueryBuilder('o')
            ->innerJoin('o.merchant', 'm')
            ->where('o.state = :finishedState')
            ->andWhere('o.unfreezeUnsplit = false')
            ->setParameter('finishedState', ProfitShareOrderState::FINISHED)
        ;

        if (null !== $merchantId) {
            $qb->andWhere('m.id = :merchantId')
                ->setParameter('merchantId', $merchantId)
            ;
        }

        // 如果不是强制解冻，则需要检查完成时间
        if (false === $forceUnfreeze) {
            $unfreezeThreshold = new \DateTimeImmutable("-{$unfreezeHours} hours");
            $qb->andWhere('o.wechatFinishedAt <= :threshold')
                ->setParameter('threshold', $unfreezeThreshold)
            ;
        }

        // 只查询最近30天内的记录
        $since = new \DateTimeImmutable('-30 days');
        $qb->andWhere('o.createTime >= :since')
            ->setParameter('since', $since)
        ;

        /** @var array<ProfitShareOrder> */
        return $qb->getQuery()->getResult();
    }
}
