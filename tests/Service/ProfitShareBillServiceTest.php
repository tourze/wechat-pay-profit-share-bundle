<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Service;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareBillTask;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareBillStatus;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareBillTaskRepository;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOperationLogRepository;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareBillDownloadRequest;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareBillRequest;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareBillService;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareBillService::class)]
class ProfitShareBillServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }

    public function testApplyBillCreatesTask(): void
    {
        // 创建Mock依赖
        $taskRepository = $this->createMock(ProfitShareBillTaskRepository::class);
        $taskRepository->expects($this->once())->method('findOneBy')->willReturn(null);
        $taskRepository->expects($this->once())->method('save');

        $operationRepository = $this->createMock(ProfitShareOperationLogRepository::class);
        $operationRepository->expects($this->once())->method('save');

        $builder = new FakeBuilderChainable([
            new Response(200, [], Json::encode([
                'hash_type' => 'SHA1',
                'hash_value' => '79bb0f45fc4c42234a918000b2668d689e2bde04',
                'download_url' => 'https://api.mch.weixin.qq.com/v3/billdownload/file?token=abc',
            ])),
        ]);

        $builderFactory = $this->createMock(WechatPayBuilder::class);
        $builderFactory->expects($this->once())->method('genBuilder')->willReturn($builder);
        $logger = $this->createMock(LoggerInterface::class);

        // 将Mock依赖注入到容器中
        self::getContainer()->set(ProfitShareBillTaskRepository::class, $taskRepository);
        self::getContainer()->set(ProfitShareOperationLogRepository::class, $operationRepository);
        self::getContainer()->set(WechatPayBuilder::class, $builderFactory);
        self::getContainer()->set(LoggerInterface::class, $logger);

        // 从容器获取服务
        $service = self::getService(ProfitShareBillService::class);

        $merchant = new Merchant();
        $merchant->setMchId('1900000001');

        $request = new ProfitShareBillRequest(
            billDate: new \DateTimeImmutable('2025-01-20'),
            subMchId: '1900000109',
            tarType: 'GZIP',
        );

        $task = $service->applyBill($merchant, $request);
        $this->assertSame(ProfitShareBillStatus::READY, $task->getStatus());
        $this->assertSame('SHA1', $task->getHashType());
    }

    public function testDownloadBillWritesFile(): void
    {
        $task = new ProfitShareBillTask();
        $task->setStatus(ProfitShareBillStatus::READY);
        $task->setDownloadUrl('https://api.mch.weixin.qq.com/v3/billdownload/file?token=abc');
        $task->setSubMchId('1900000109');

        // 创建Mock依赖
        $taskRepository = $this->createMock(ProfitShareBillTaskRepository::class);
        $taskRepository->expects($this->once())->method('save');

        $operationRepository = $this->createMock(ProfitShareOperationLogRepository::class);
        $operationRepository->expects($this->once())->method('save');

        $builder = new FakeBuilderChainable([
            new Response(200, [], 'file-content'),
        ]);

        $builderFactory = $this->createMock(WechatPayBuilder::class);
        $builderFactory->expects($this->once())->method('genBuilder')->willReturn($builder);
        $logger = $this->createMock(LoggerInterface::class);

        // 将Mock依赖注入到容器中
        self::getContainer()->set(ProfitShareBillTaskRepository::class, $taskRepository);
        self::getContainer()->set(ProfitShareOperationLogRepository::class, $operationRepository);
        self::getContainer()->set(WechatPayBuilder::class, $builderFactory);
        self::getContainer()->set(LoggerInterface::class, $logger);

        // 从容器获取服务
        $service = self::getService(ProfitShareBillService::class);

        $merchant = new Merchant();
        $merchant->setMchId('1900000001');

        $tmpFile = tempnam(sys_get_temp_dir(), 'bill');
        $downloadRequest = new ProfitShareBillDownloadRequest(
            downloadUrl: $task->getDownloadUrl(),
            localPath: $tmpFile,
        );

        $service->downloadBill($merchant, $task, $downloadRequest);

        $this->assertFileExists($tmpFile);
        $this->assertSame('file-content', file_get_contents($tmpFile));
        $this->assertSame(ProfitShareBillStatus::DOWNLOADED, $task->getStatus());

        @unlink($tmpFile);
    }
}
