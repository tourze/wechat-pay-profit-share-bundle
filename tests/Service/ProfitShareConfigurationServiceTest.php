<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Service;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Psr\Log\LoggerInterface;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOperationLogRepository;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareConfigurationService;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareConfigurationService::class)]
class ProfitShareConfigurationServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }
    public function testQueryRemainingAmount(): void
    {
        // 创建Mock依赖
        $operationRepository = $this->createMock(ProfitShareOperationLogRepository::class);
        $operationRepository->expects($this->once())->method('save');

        $builder = new FakeBuilderChainable([
            new Response(200, [], Json::encode([
                'transaction_id' => '4208450740201411110007820472',
                'unsplit_amount' => 1000,
            ])),
        ]);

        $builderFactory = $this->createMock(WechatPayBuilder::class);
        $builderFactory->expects($this->once())->method('genBuilder')->willReturn($builder);

        // 将Mock依赖注入到容器中
        self::getContainer()->set(ProfitShareOperationLogRepository::class, $operationRepository);
        self::getContainer()->set(WechatPayBuilder::class, $builderFactory);

        // 从容器获取服务
        $service = self::getService(ProfitShareConfigurationService::class);

        $merchant = new Merchant();
        $merchant->setMchId('1900000001');

        $result = $service->queryRemainingAmount($merchant, '4208450740201411110007820472');
        $this->assertSame(1000, $result['unsplit_amount']);
    }

    public function testQueryMaxRatio(): void
    {
        // 创建Mock依赖
        $operationRepository = $this->createMock(ProfitShareOperationLogRepository::class);
        $operationRepository->expects($this->once())->method('save');

        $builder = new FakeBuilderChainable([
            new Response(200, [], Json::encode([
                'sub_mchid' => '1900000109',
                'max_ratio' => 2000,
            ])),
        ]);

        $builderFactory = $this->createMock(WechatPayBuilder::class);
        $builderFactory->expects($this->once())->method('genBuilder')->willReturn($builder);

        // 将Mock依赖注入到容器中
        self::getContainer()->set(ProfitShareOperationLogRepository::class, $operationRepository);
        self::getContainer()->set(WechatPayBuilder::class, $builderFactory);

        // 从容器获取服务
        $service = self::getService(ProfitShareConfigurationService::class);

        $merchant = new Merchant();
        $merchant->setMchId('1900000001');

        $result = $service->queryMaxRatio($merchant, '1900000109');
        $this->assertSame(2000, $result['max_ratio']);
    }
}
