<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReceiver;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareReceiverResult;

/**
 * 微信支付分账接收方数据填充
 * 创建测试用的分账接收方数据
 */
#[When(env: 'test')]
#[When(env: 'dev')]
class ProfitShareReceiverFixtures extends Fixture implements FixtureGroupInterface
{
    public const RECEIVER_PENDING_REFERENCE = 'receiver-pending';
    public const RECEIVER_SUCCESS_REFERENCE = 'receiver-success';
    public const RECEIVER_FAILED_REFERENCE = 'receiver-failed';
    public const RECEIVER_RETRY_REFERENCE = 'receiver-retry';

    public static function getGroups(): array
    {
        return ['test', 'dev'];
    }

    public function load(ObjectManager $manager): void
    {
        // 获取分账订单引用
        /** @var ProfitShareOrder $orderProcessing */
        $orderProcessing = $this->getReference(ProfitShareOrderFixtures::ORDER_PROCESSING_REFERENCE, ProfitShareOrder::class);

        /** @var ProfitShareOrder $orderFinished */
        $orderFinished = $this->getReference(ProfitShareOrderFixtures::ORDER_FINISHED_REFERENCE, ProfitShareOrder::class);

        // 创建待处理的分账接收方
        $receiverPending = new ProfitShareReceiver();
        $receiverPending->setOrder($orderProcessing);
        $receiverPending->setSequence(0);
        $receiverPending->setType('MERCHANT_ID');
        $receiverPending->setAccount('1900000109');
        $receiverPending->setAmount(100);
        $receiverPending->setDescription('分给商户');
        $receiverPending->setResult(ProfitShareReceiverResult::PENDING);
        $receiverPending->setRetryCount(0);
        $receiverPending->setFinallyFailed(false);
        $receiverPending->setMetadata([
            'receiver_name' => '测试商户',
            'original_amount' => 100,
            'fee_amount' => 0,
            'actual_amount' => 100,
        ]);
        $manager->persist($receiverPending);
        $this->addReference(self::RECEIVER_PENDING_REFERENCE, $receiverPending);

        // 创建成功的分账接收方（商户）
        $receiverSuccessMerchant = new ProfitShareReceiver();
        $receiverSuccessMerchant->setOrder($orderFinished);
        $receiverSuccessMerchant->setSequence(0);
        $receiverSuccessMerchant->setType('MERCHANT_ID');
        $receiverSuccessMerchant->setAccount('1900000109');
        $receiverSuccessMerchant->setName('加密的商户姓名密文');
        $receiverSuccessMerchant->setAmount(500);
        $receiverSuccessMerchant->setDescription('分给商户');
        $receiverSuccessMerchant->setResult(ProfitShareReceiverResult::SUCCESS);
        $receiverSuccessMerchant->setWechatCreatedAt(new \DateTimeImmutable('2024-01-14 09:15:00'));
        $receiverSuccessMerchant->setWechatFinishedAt(new \DateTimeImmutable('2024-01-14 09:15:15'));
        $receiverSuccessMerchant->setDetailId('3600002024011400990023456789');
        $receiverSuccessMerchant->setRetryCount(0);
        $receiverSuccessMerchant->setFinallyFailed(false);
        $receiverSuccessMerchant->setMetadata([
            'receiver_name' => '测试商户',
            'original_amount' => 500,
            'fee_amount' => 0,
            'actual_amount' => 500,
            'settlement_time' => '2024-01-14 09:15:15',
            'settlement_amount' => 500,
        ]);
        $manager->persist($receiverSuccessMerchant);
        $this->addReference(self::RECEIVER_SUCCESS_REFERENCE, $receiverSuccessMerchant);

        // 创建成功的分账接收方（个人）
        $receiverSuccessPersonal = new ProfitShareReceiver();
        $receiverSuccessPersonal->setOrder($orderFinished);
        $receiverSuccessPersonal->setSequence(1);
        $receiverSuccessPersonal->setType('PERSONAL_OPENID');
        $receiverSuccessPersonal->setAccount('oxRHG5p6J9nW1z2y3x4w5v6u7t8');
        $receiverSuccessPersonal->setName('加密的个人姓名密文');
        $receiverSuccessPersonal->setAmount(200);
        $receiverSuccessPersonal->setDescription('分给个人');
        $receiverSuccessPersonal->setResult(ProfitShareReceiverResult::SUCCESS);
        $receiverSuccessPersonal->setWechatCreatedAt(new \DateTimeImmutable('2024-01-14 09:15:00'));
        $receiverSuccessPersonal->setWechatFinishedAt(new \DateTimeImmutable('2024-01-14 09:15:30'));
        $receiverSuccessPersonal->setDetailId('3600002024011400990023456790');
        $receiverSuccessPersonal->setRetryCount(0);
        $receiverSuccessPersonal->setFinallyFailed(false);
        $receiverSuccessPersonal->setMetadata([
            'receiver_name' => '张三',
            'original_amount' => 200,
            'fee_amount' => 1,
            'actual_amount' => 199,
            'settlement_time' => '2024-01-14 09:15:30',
            'settlement_amount' => 199,
        ]);
        $manager->persist($receiverSuccessPersonal);

        // 创建失败的分账接收方
        $receiverFailed = new ProfitShareReceiver();
        $receiverFailed->setOrder($orderProcessing);
        $receiverFailed->setSequence(1);
        $receiverFailed->setType('PERSONAL_OPENID');
        $receiverFailed->setAccount('oxRHG5p6J9nW1z2y3x4w5v6u7t9');
        $receiverFailed->setAmount(150);
        $receiverFailed->setDescription('分给个人用户');
        $receiverFailed->setResult(ProfitShareReceiverResult::FAILED);
        $receiverFailed->setFailReason('ACCOUNT_NOT_EXIST');
        $receiverFailed->setWechatCreatedAt(new \DateTimeImmutable('2024-01-15 10:30:00'));
        $receiverFailed->setWechatFinishedAt(new \DateTimeImmutable('2024-01-15 10:30:25'));
        $receiverFailed->setRetryCount(3);
        $receiverFailed->setFinallyFailed(true);
        $receiverFailed->setMetadata([
            'receiver_name' => '李四',
            'original_amount' => 150,
            'error_code' => 'ACCOUNT_NOT_EXIST',
            'error_message' => '分账接收方账户不存在',
            'first_failed_at' => '2024-01-15 10:30:25',
            'last_failed_at' => '2024-01-15 11:00:25',
        ]);
        $manager->persist($receiverFailed);
        $this->addReference(self::RECEIVER_FAILED_REFERENCE, $receiverFailed);

        // 创建需要重试的分账接收方
        $receiverRetry = new ProfitShareReceiver();
        $receiverRetry->setOrder($orderProcessing);
        $receiverRetry->setSequence(2);
        $receiverRetry->setType('MERCHANT_ID');
        $receiverRetry->setAccount('1900000110');
        $receiverRetry->setAmount(80);
        $receiverRetry->setDescription('分给合作商户');
        $receiverRetry->setResult(ProfitShareReceiverResult::PENDING);
        $receiverRetry->setFailReason('SYSTEM_ERROR');
        $receiverRetry->setWechatCreatedAt(new \DateTimeImmutable('2024-01-15 10:30:00'));
        $receiverRetry->setRetryCount(1);
        $receiverRetry->setNextRetryAt(new \DateTimeImmutable('2024-01-15 11:00:00'));
        $receiverRetry->setFinallyFailed(false);
        $receiverRetry->setMetadata([
            'receiver_name' => '合作商户',
            'original_amount' => 80,
            'error_code' => 'SYSTEM_ERROR',
            'error_message' => '系统繁忙，请稍后再试',
            'first_failed_at' => '2024-01-15 10:35:00',
            'retry_interval' => 1800,
        ]);
        $manager->persist($receiverRetry);
        $this->addReference(self::RECEIVER_RETRY_REFERENCE, $receiverRetry);

        // 创建已关闭的分账接收方
        $receiverClosed = new ProfitShareReceiver();
        $receiverClosed->setOrder($orderProcessing);
        $receiverClosed->setSequence(3);
        $receiverClosed->setType('PERSONAL_OPENID');
        $receiverClosed->setAccount('oxRHG5p6J9nW1z2y3x4w5v6u7t0');
        $receiverClosed->setAmount(120);
        $receiverClosed->setDescription('分给推广员');
        $receiverClosed->setResult(ProfitShareReceiverResult::CLOSED);
        $receiverClosed->setFailReason('ORDER_CLOSED');
        $receiverClosed->setWechatCreatedAt(new \DateTimeImmutable('2024-01-15 10:30:00'));
        $receiverClosed->setWechatFinishedAt(new \DateTimeImmutable('2024-01-15 10:45:00'));
        $receiverClosed->setRetryCount(0);
        $receiverClosed->setFinallyFailed(false);
        $receiverClosed->setMetadata([
            'receiver_name' => '王五',
            'original_amount' => 120,
            'close_reason' => '分账订单已关闭',
            'close_time' => '2024-01-15 10:45:00',
        ]);
        $manager->persist($receiverClosed);

        $manager->flush();
    }
}
