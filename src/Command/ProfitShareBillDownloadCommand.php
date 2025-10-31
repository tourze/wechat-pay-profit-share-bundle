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
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareBillTask;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareBillStatus;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareBillTaskRepository;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareBillDownloadRequest;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareBillService;
use WechatPayBundle\Entity\Merchant;

#[AsCommand(
    name: 'wechat:profit-share:download-bill',
    description: '自动下载微信支付分账账单'
)]
#[WithMonologChannel(channel: 'wechat_pay_profit_share')]
class ProfitShareBillDownloadCommand extends Command
{
    public function __construct(
        private readonly ProfitShareBillTaskRepository $billTaskRepository,
        private readonly ProfitShareBillService $billService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '只模拟执行，不实际下载数据')
            ->addOption('expire-days', null, InputOption::VALUE_OPTIONAL, '账单过期天数', 7)
            ->addOption('download-path', null, InputOption::VALUE_OPTIONAL, '下载存储路径', '/var/data/wechat-profit-share-bills')
            ->addOption('merchant-id', null, InputOption::VALUE_OPTIONAL, '指定商户ID，不指定则处理所有商户')
            ->setHelp('
此命令用于自动下载准备就绪的分账账单文件。

使用示例：
  # 下载所有就绪的账单
  php bin/console wechat:profit-share:download-bill

  # 模拟执行，不实际下载
  php bin/console wechat:profit-share:download-bill --dry-run

  # 指定下载路径
  php bin/console wechat:profit-share:download-bill --download-path=/path/to/bills

  # 指定商户
  php bin/console wechat:profit-share:download-bill --merchant-id=123

  # 设置过期天数为10天
  php bin/console wechat:profit-share:download-bill --expire-days=10
            ')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRunOption = $input->getOption('dry-run');
        $expireDaysOption = $input->getOption('expire-days');
        $downloadPathOption = $input->getOption('download-path');
        $merchantIdOption = $input->getOption('merchant-id');

        $dryRun = \is_bool($dryRunOption) ? $dryRunOption : false;
        $expireDays = \is_int($expireDaysOption) || \is_string($expireDaysOption) ? (int) $expireDaysOption : 7;
        $downloadPath = \is_string($downloadPathOption) ? $downloadPathOption : '/var/data/wechat-profit-share-bills';
        $merchantId = \is_string($merchantIdOption) ? $merchantIdOption : null;

        $io->title('微信支付分账账单自动下载');

        if (true === $dryRun) {
            $io->warning('当前为模拟执行模式，不会实际下载文件');
        }

        try {
            $readyTasks = $this->findReadyTasks($merchantId);

            if (0 === count($readyTasks)) {
                $io->success('没有需要下载的账单');
                return Command::SUCCESS;
            }

            $io->section('开始下载账单文件');
            $io->progressStart(count($readyTasks));

            $result = $this->processTasks($readyTasks, $dryRun, $expireDays, $downloadPath);

            $io->progressFinish();
            $this->displayResults($io, $result, count($readyTasks));
            $this->displayWarnings($io, $result);

            $io->success('账单下载完成');
        } catch (\Throwable $exception) {
            $this->logger->error('账单下载命令执行失败', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $io->error('下载失败：' . $exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * 查找准备就绪的账单任务
     *
     * @return array<ProfitShareBillTask>
     */
    private function findReadyTasks(?string $merchantId): array
    {
        $criteria = ['status' => ProfitShareBillStatus::READY];
        if ($merchantId !== null) {
            $criteria['merchant'] = $merchantId;
        }

        return $this->billTaskRepository->findBy($criteria);
    }

    /**
     * 处理账单任务
     *
     * @param array<ProfitShareBillTask> $readyTasks
     * @return array{downloaded: int, expired: int, error: int}
     */
    private function processTasks(array $readyTasks, bool $dryRun, int $expireDays, string $downloadPath): array
    {
        $downloadedCount = 0;
        $expiredCount = 0;
        $errorCount = 0;
        $expireThreshold = new \DateTimeImmutable("-{$expireDays} days");

        foreach ($readyTasks as $task) {
            try {
                $result = $this->processTask($task, $dryRun, $expireDays, $downloadPath, $expireThreshold);

                if ($result['downloaded']) {
                    ++$downloadedCount;
                }
                if ($result['expired']) {
                    ++$expiredCount;
                }
                if ($result['error']) {
                    ++$errorCount;
                }
            } catch (\Throwable $exception) {
                ++$errorCount;
                $this->logger->error('下载账单失败', [
                    'task_id' => $task->getId(),
                    'sub_mchid' => $task->getSubMchId(),
                    'bill_date' => $task->getBillDate()->format('Y-m-d'),
                    'error' => $exception->getMessage(),
                ]);

                if (!$dryRun) {
                    $task->setStatus(ProfitShareBillStatus::FAILED);
                    $this->billTaskRepository->save($task);
                }
            }
        }

        return [
            'downloaded' => $downloadedCount,
            'expired' => $expiredCount,
            'error' => $errorCount,
        ];
    }

    /**
     * 处理单个账单任务
     *
     * @return array{downloaded: bool, expired: bool, error: bool}
     */
    private function processTask(ProfitShareBillTask $task, bool $dryRun, int $expireDays, string $downloadPath, \DateTimeImmutable $expireThreshold): array
    {
        // 检查账单是否过期
        if ($task->getCreatedAt() < $expireThreshold) {
            $this->handleExpiredTask($task, $dryRun, $expireDays);
            return ['downloaded' => false, 'expired' => true, 'error' => false];
        }

        // 获取商户信息
        $merchant = $task->getMerchant();
        if (null === $merchant) {
            $this->logger->error('账单任务缺少商户信息', [
                'task_id' => $task->getId(),
                'sub_mchid' => $task->getSubMchId(),
                'bill_date' => $task->getBillDate()->format('Y-m-d'),
            ]);
            return ['downloaded' => false, 'expired' => false, 'error' => true];
        }

        // 生成本地文件路径
        $localPath = $this->generateLocalPath($downloadPath, $task);

        if (!$dryRun) {
            return $this->executeDownload($task, $merchant, $localPath);
        }

        // 模拟下载成功
        $this->logger->info('模拟账单下载', [
            'task_id' => $task->getId(),
            'sub_mchid' => $task->getSubMchId(),
            'bill_date' => $task->getBillDate()->format('Y-m-d'),
            'local_path' => $localPath,
        ]);

        return ['downloaded' => true, 'expired' => false, 'error' => false];
    }

    /**
     * 处理过期任务
     */
    private function handleExpiredTask(ProfitShareBillTask $task, bool $dryRun, int $expireDays): void
    {
        $createdAtStr = $task->getCreatedAt() !== null ? $task->getCreatedAt()->format('Y-m-d H:i:s') : 'unknown';
        $this->logger->warning('账单任务已过期', [
            'task_id' => $task->getId(),
            'sub_mchid' => $task->getSubMchId(),
            'bill_date' => $task->getBillDate()->format('Y-m-d'),
            'created_at' => $createdAtStr,
            'expire_days' => $expireDays,
        ]);

        if (!$dryRun) {
            $task->setStatus(ProfitShareBillStatus::EXPIRED);
            $this->billTaskRepository->save($task);
        }
    }

    /**
     * 执行下载
     *
     * @return array{downloaded: bool, expired: bool, error: bool}
     */
    private function executeDownload(ProfitShareBillTask $task, Merchant $merchant, string $localPath): array
    {
        // 创建下载请求
        $downloadRequest = new ProfitShareBillDownloadRequest();
        $downloadRequest->setLocalPath($localPath);
        $downloadRequest->setExpectedHashType($task->getHashType());
        $downloadRequest->setExpectedHashValue($task->getHashValue());
        $downloadRequest->setTarType($task->getTarType());

        // 执行下载
        $updatedTask = $this->billService->downloadBill($merchant, $task, $downloadRequest);

        if (ProfitShareBillStatus::DOWNLOADED === $updatedTask->getStatus()) {
            $this->logger->info('账单下载成功', [
                'task_id' => $task->getId(),
                'sub_mchid' => $task->getSubMchId(),
                'bill_date' => $task->getBillDate()->format('Y-m-d'),
                'local_path' => $localPath,
                'file_size' => file_exists($localPath) ? filesize($localPath) : 0,
            ]);
            return ['downloaded' => true, 'expired' => false, 'error' => false];
        }

        return ['downloaded' => false, 'expired' => false, 'error' => true];
    }

    /**
     * 显示结果统计
     *
     * @param array{downloaded: int, expired: int, error: int} $result
     */
    private function displayResults(SymfonyStyle $io, array $result, int $totalTasks): void
    {
        $io->section('下载结果统计');
        $io->table(
            ['类型', '数量'],
            [
                ['总任务数', $totalTasks],
                ['下载成功数', $result['downloaded']],
                ['过期任务数', $result['expired']],
                ['错误任务数', $result['error']],
            ]
        );
    }

    /**
     * 显示警告信息
     *
     * @param array{downloaded: int, expired: int, error: int} $result
     */
    private function displayWarnings(SymfonyStyle $io, array $result): void
    {
        if ($result['error'] > 0) {
            $io->warning("发现 {$result['error']} 个任务下载失败，请查看日志");
        }

        if ($result['expired'] > 0) {
            $io->warning("发现 {$result['expired']} 个过期任务，已标记为过期状态");
        }
    }

    /**
     * 生成本地文件路径
     */
    private function generateLocalPath(string $basePath, ProfitShareBillTask $task): string
    {
        $billDate = $task->getBillDate()->format('Y-m-d');
        $subMchId = $task->getSubMchId() !== null ? $task->getSubMchId() : 'default';
        $tarType = $task->getTarType() !== null ? $task->getTarType() : 'plain';

        // 创建目录结构：basePath/year/month/
        $year = $task->getBillDate()->format('Y');
        $month = $task->getBillDate()->format('m');
        $dir = sprintf('%s/%s/%s/%s', $basePath, $year, $month, $subMchId);

        // 生成文件名
        $filename = sprintf('profit_share_%s_%s.%s', $billDate, $subMchId, strtolower($tarType));

        return sprintf('%s/%s', $dir, $filename);
    }
}
