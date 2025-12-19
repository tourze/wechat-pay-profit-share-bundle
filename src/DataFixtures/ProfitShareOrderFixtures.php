<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOrderState;
use WechatPayBundle\DataFixtures\MerchantFixtures;
use WechatPayBundle\Entity\Merchant;

/**
 * 微信支付分账订单数据填充
 * 创建测试用的分账订单数据
 */
#[When(env: 'test')]
#[When(env: 'dev')]
final class ProfitShareOrderFixtures extends Fixture implements FixtureGroupInterface
{
    public const ORDER_PROCESSING_REFERENCE = 'order-processing';
    public const ORDER_FINISHED_REFERENCE = 'order-finished';
    public const ORDER_CLOSED_REFERENCE = 'order-closed';

    public static function getGroups(): array
    {
        return ['test', 'dev'];
    }

    public function load(ObjectManager $manager): void
    {
        // 获取测试商户
        /** @var Merchant $testMerchant */
        $testMerchant = $this->getReference(MerchantFixtures::TEST_MERCHANT_REFERENCE, Merchant::class);

        // 创建处理中的分账订单
        $orderProcessing = new ProfitShareOrder();
        $orderProcessing->setMerchant($testMerchant);
        $orderProcessing->setSubMchId('1900000109');
        $orderProcessing->setAppId('wxd678efh567hg6992');
        $orderProcessing->setTransactionId('4200000452202401158754321234');
        $orderProcessing->setOutOrderNo('P202401150001');
        $orderProcessing->setState(ProfitShareOrderState::PROCESSING);
        $orderProcessing->setUnfreezeUnsplit(false);
        $orderProcessing->setRequestPayload(json_encode([
            'sub_mch_id' => '1900000109',
            'transaction_id' => '4200000452202401158754321234',
            'out_order_no' => 'P202401150001',
            'receivers' => [
                [
                    'type' => 'MERCHANT_ID',
                    'account' => '1900000109',
                    'amount' => 100,
                    'description' => '分给商户',
                ],
            ],
        ], JSON_THROW_ON_ERROR));
        $orderProcessing->setResponsePayload(json_encode([
            'sub_mch_id' => '1900000109',
            'transaction_id' => '4200000452202401158754321234',
            'order_id' => '3008450740201411110007820472',
            'out_order_no' => 'P202401150001',
            'state' => 'PROCESSING',
        ], JSON_THROW_ON_ERROR));
        $orderProcessing->setMetadata([
            'total_amount' => 10000,
            'receivers_count' => 1,
            'request_time' => '2024-01-15 10:30:00',
            'auto_unfreeze' => false,
        ]);
        $manager->persist($orderProcessing);
        $this->addReference(self::ORDER_PROCESSING_REFERENCE, $orderProcessing);

        // 创建已完成的分账订单
        $orderFinished = new ProfitShareOrder();
        $orderFinished->setMerchant($testMerchant);
        $orderFinished->setSubMchId('1900000109');
        $orderFinished->setAppId('wxd678efh567hg6992');
        $orderFinished->setTransactionId('4200000452202401148765432109');
        $orderFinished->setOutOrderNo('P202401140001');
        $orderFinished->setOrderId('3008450740201411110007820471');
        $orderFinished->setState(ProfitShareOrderState::FINISHED);
        $orderFinished->setUnfreezeUnsplit(true);
        $orderFinished->setWechatCreatedAt(new \DateTimeImmutable('2024-01-14 09:15:00'));
        $orderFinished->setWechatFinishedAt(new \DateTimeImmutable('2024-01-14 09:15:30'));
        $orderFinished->setRequestPayload(json_encode([
            'sub_mch_id' => '1900000109',
            'transaction_id' => '4200000452202401148765432109',
            'out_order_no' => 'P202401140001',
            'unfreeze_unsplit' => true,
            'receivers' => [
                [
                    'type' => 'MERCHANT_ID',
                    'account' => '1900000109',
                    'amount' => 500,
                    'description' => '分给商户',
                ],
                [
                    'type' => 'PERSONAL_OPENID',
                    'account' => 'oxRHG5p6J9nW1z2y3x4w5v6u7t8',
                    'amount' => 200,
                    'description' => '分给个人',
                ],
            ],
        ], JSON_THROW_ON_ERROR));
        $orderFinished->setResponsePayload(json_encode([
            'sub_mch_id' => '1900000109',
            'transaction_id' => '4200000452202401148765432109',
            'order_id' => '3008450740201411110007820471',
            'out_order_no' => 'P202401140001',
            'state' => 'FINISHED',
            'receivers' => [
                [
                    'type' => 'MERCHANT_ID',
                    'account' => '1900000109',
                    'amount' => 500,
                    'description' => '分给商户',
                    'result' => 'SUCCESS',
                    'detail_id' => '3600002024011400990023456789',
                ],
                [
                    'type' => 'PERSONAL_OPENID',
                    'account' => 'oxRHG5p6J9nW1z2y3x4w5v6u7t8',
                    'amount' => 200,
                    'description' => '分给个人',
                    'result' => 'SUCCESS',
                    'detail_id' => '3600002024011400990023456790',
                ],
            ],
            'unfreeze_unsplit_amount' => 4300,
        ], JSON_THROW_ON_ERROR));
        $orderFinished->setMetadata([
            'total_amount' => 10000,
            'shared_amount' => 700,
            'unsplit_amount' => 4300,
            'receivers_count' => 2,
            'success_count' => 2,
            'failed_count' => 0,
            'processing_duration' => 30,
        ]);
        $manager->persist($orderFinished);
        $this->addReference(self::ORDER_FINISHED_REFERENCE, $orderFinished);

        // 创建已关闭的分账订单
        $orderClosed = new ProfitShareOrder();
        $orderClosed->setMerchant($testMerchant);
        $orderClosed->setSubMchId('1900000109');
        $orderClosed->setAppId('wxd678efh567hg6992');
        $orderClosed->setTransactionId('4200000452202401139876543210');
        $orderClosed->setOutOrderNo('P202401130001');
        $orderClosed->setOrderId('3008450740201411110007820470');
        $orderClosed->setState(ProfitShareOrderState::CLOSED);
        $orderClosed->setUnfreezeUnsplit(false);
        $orderClosed->setWechatCreatedAt(new \DateTimeImmutable('2024-01-13 14:20:00'));
        $orderClosed->setWechatFinishedAt(new \DateTimeImmutable('2024-01-13 14:22:15'));
        $orderClosed->setRequestPayload(json_encode([
            'sub_mch_id' => '1900000109',
            'transaction_id' => '4200000452202401139876543210',
            'out_order_no' => 'P202401130001',
            'receivers' => [
                [
                    'type' => 'MERCHANT_ID',
                    'account' => '1900000109',
                    'amount' => 300,
                    'description' => '分给商户',
                ],
            ],
        ], JSON_THROW_ON_ERROR));
        $orderClosed->setResponsePayload(json_encode([
            'sub_mch_id' => '1900000109',
            'transaction_id' => '4200000452202401139876543210',
            'order_id' => '3008450740201411110007820470',
            'out_order_no' => 'P202401130001',
            'state' => 'CLOSED',
        ], JSON_THROW_ON_ERROR));
        $orderClosed->setMetadata([
            'total_amount' => 8000,
            'shared_amount' => 300,
            'receivers_count' => 1,
            'close_reason' => 'USER_REFUND',
            'processing_duration' => 135,
        ]);
        $manager->persist($orderClosed);
        $this->addReference(self::ORDER_CLOSED_REFERENCE, $orderClosed);

        $manager->flush();
    }
}
