<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareBillTask;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareBillStatus;
use WechatPayBundle\DataFixtures\MerchantFixtures;
use WechatPayBundle\Entity\Merchant;

/**
 * 微信支付分账账单任务数据填充
 * 创建测试用的分账账单任务数据
 */
#[When(env: 'test')]
#[When(env: 'dev')]
final class ProfitShareBillTaskFixtures extends Fixture implements FixtureGroupInterface
{
    public const BILL_TASK_PENDING_REFERENCE = 'bill-task-pending';
    public const BILL_TASK_READY_REFERENCE = 'bill-task-ready';
    public const BILL_TASK_DOWNLOADED_REFERENCE = 'bill-task-downloaded';
    public const BILL_TASK_FAILED_REFERENCE = 'bill-task-failed';

    public static function getGroups(): array
    {
        return ['test', 'dev'];
    }

    public function load(ObjectManager $manager): void
    {
        // 获取测试商户
        /** @var Merchant $testMerchant */
        $testMerchant = $this->getReference(MerchantFixtures::TEST_MERCHANT_REFERENCE, Merchant::class);

        // 创建待生成的账单任务
        $billTaskPending = new ProfitShareBillTask();
        $billTaskPending->setMerchant($testMerchant);
        $billTaskPending->setSubMchId('1900000109');
        $billTaskPending->setBillDate(new \DateTimeImmutable('2024-01-15'));
        $billTaskPending->setTarType('GZIP');
        $billTaskPending->setStatus(ProfitShareBillStatus::PENDING);
        $billTaskPending->setRequestPayload(json_encode([
            'sub_mch_id' => '1900000109',
            'bill_date' => '2024-01-15',
            'tar_type' => 'GZIP',
        ], JSON_THROW_ON_ERROR));
        $billTaskPending->setMetadata([
            'created_by' => 'system',
            'auto_retry' => true,
            'retry_count' => 0,
        ]);
        $manager->persist($billTaskPending);
        $this->addReference(self::BILL_TASK_PENDING_REFERENCE, $billTaskPending);

        // 创建可下载的账单任务
        $billTaskReady = new ProfitShareBillTask();
        $billTaskReady->setMerchant($testMerchant);
        $billTaskReady->setSubMchId('1900000109');
        $billTaskReady->setBillDate(new \DateTimeImmutable('2024-01-14'));
        $billTaskReady->setTarType('GZIP');
        $billTaskReady->setHashType('SHA1');
        $billTaskReady->setHashValue('e1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p7q8r9s0');
        $billTaskReady->setDownloadUrl('https://api.mch.weixin.qq.com/v3/profitsharing/bills?file_token=xxxxx');
        $billTaskReady->setStatus(ProfitShareBillStatus::READY);
        $billTaskReady->setRequestPayload(json_encode([
            'sub_mch_id' => '1900000109',
            'bill_date' => '2024-01-14',
            'tar_type' => 'GZIP',
        ], JSON_THROW_ON_ERROR));
        $billTaskReady->setResponsePayload(json_encode([
            'download_url' => 'https://api.mch.weixin.qq.com/v3/profitsharing/bills?file_token=xxxxx',
            'hash_type' => 'SHA1',
            'hash_value' => 'e1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p7q8r9s0',
        ], JSON_THROW_ON_ERROR));
        $billTaskReady->setMetadata([
            'created_by' => 'system',
            'expires_at' => '2024-01-21 23:59:59',
        ]);
        $manager->persist($billTaskReady);
        $this->addReference(self::BILL_TASK_READY_REFERENCE, $billTaskReady);

        // 创建已下载的账单任务
        $billTaskDownloaded = new ProfitShareBillTask();
        $billTaskDownloaded->setMerchant($testMerchant);
        $billTaskDownloaded->setSubMchId('1900000109');
        $billTaskDownloaded->setBillDate(new \DateTimeImmutable('2024-01-13'));
        $billTaskDownloaded->setTarType('GZIP');
        $billTaskDownloaded->setHashType('SHA1');
        $billTaskDownloaded->setHashValue('a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0');
        $billTaskDownloaded->setDownloadUrl('https://api.mch.weixin.qq.com/v3/profitsharing/bills?file_token=yyyyy');
        $billTaskDownloaded->setStatus(ProfitShareBillStatus::DOWNLOADED);
        $billTaskDownloaded->setDownloadedAt(new \DateTimeImmutable('2024-01-13 10:30:00'));
        $billTaskDownloaded->setLocalPath('/var/tmp/profit_share_bills/1900000109_20240113.gz');
        $billTaskDownloaded->setRequestPayload(json_encode([
            'sub_mch_id' => '1900000109',
            'bill_date' => '2024-01-13',
            'tar_type' => 'GZIP',
        ], JSON_THROW_ON_ERROR));
        $billTaskDownloaded->setResponsePayload(json_encode([
            'download_url' => 'https://api.mch.weixin.qq.com/v3/profitsharing/bills?file_token=yyyyy',
            'hash_type' => 'SHA1',
            'hash_value' => 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0',
        ], JSON_THROW_ON_ERROR));
        $billTaskDownloaded->setMetadata([
            'created_by' => 'system',
            'file_size' => 2048576,
            'record_count' => 1567,
            'download_duration' => 3.45,
        ]);
        $manager->persist($billTaskDownloaded);
        $this->addReference(self::BILL_TASK_DOWNLOADED_REFERENCE, $billTaskDownloaded);

        // 创建失败的账单任务
        $billTaskFailed = new ProfitShareBillTask();
        $billTaskFailed->setMerchant($testMerchant);
        $billTaskFailed->setSubMchId('1900000109');
        $billTaskFailed->setBillDate(new \DateTimeImmutable('2024-01-12'));
        $billTaskFailed->setTarType('GZIP');
        $billTaskFailed->setStatus(ProfitShareBillStatus::FAILED);
        $billTaskFailed->setRequestPayload(json_encode([
            'sub_mch_id' => '1900000109',
            'bill_date' => '2024-01-12',
            'tar_type' => 'GZIP',
        ], JSON_THROW_ON_ERROR));
        $billTaskFailed->setResponsePayload(json_encode([
            'code' => 'BILL_NOT_EXIST',
            'message' => '账单不存在',
        ], JSON_THROW_ON_ERROR));
        $billTaskFailed->setMetadata([
            'created_by' => 'system',
            'error_code' => 'BILL_NOT_EXIST',
            'error_message' => '账单不存在',
            'retry_count' => 3,
            'last_retry_at' => '2024-01-12 15:45:00',
        ]);
        $manager->persist($billTaskFailed);
        $this->addReference(self::BILL_TASK_FAILED_REFERENCE, $billTaskFailed);

        $manager->flush();
    }
}
