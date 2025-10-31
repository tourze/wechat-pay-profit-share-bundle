<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReturnOrder;
use WechatPayBundle\DataFixtures\MerchantFixtures;
use WechatPayBundle\Entity\Merchant;

/**
 * 微信支付分账回退单数据填充
 * 创建测试用的分账回退单数据
 */
#[When(env: 'test')]
#[When(env: 'dev')]
class ProfitShareReturnOrderFixtures extends Fixture implements FixtureGroupInterface
{
    public const RETURN_ORDER_PROCESSING_REFERENCE = 'return-order-processing';
    public const RETURN_ORDER_SUCCESS_REFERENCE = 'return-order-success';
    public const RETURN_ORDER_FAILED_REFERENCE = 'return-order-failed';

    public static function getGroups(): array
    {
        return ['test', 'dev'];
    }

    public function load(ObjectManager $manager): void
    {
        // 获取测试商户
        /** @var Merchant $testMerchant */
        $testMerchant = $this->getReference(MerchantFixtures::TEST_MERCHANT_REFERENCE, Merchant::class);

        // 创建处理中的分账回退单
        $returnOrderProcessing = new ProfitShareReturnOrder();
        $returnOrderProcessing->setMerchant($testMerchant);
        $returnOrderProcessing->setSubMchId('1900000109');
        $returnOrderProcessing->setOrderId('3008450740201411110007820472');
        $returnOrderProcessing->setOutOrderNo('P202401150001');
        $returnOrderProcessing->setOutReturnNo('R202401150001');
        $returnOrderProcessing->setAmount(300);
        $returnOrderProcessing->setDescription('用户申请退款，回退分账金额');
        $returnOrderProcessing->setRequestPayload(json_encode([
            'sub_mch_id' => '1900000109',
            'order_id' => '3008450740201411110007820472',
            'out_order_no' => 'P202401150001',
            'out_return_no' => 'R202401150001',
            'return_mch_id' => '1900000109',
            'amount' => 300,
            'description' => '用户申请退款，回退分账金额'
        ], JSON_THROW_ON_ERROR));
        $returnOrderProcessing->setResponsePayload(json_encode([
            'sub_mch_id' => '1900000109',
            'return_no' => '50000000000000000000000000000000',
            'out_order_no' => 'P202401150001',
            'out_return_no' => 'R202401150001',
            'order_id' => '3008450740201411110007820472',
            'state' => 'PROCESSING',
            'amount' => 300
        ], JSON_THROW_ON_ERROR));
        $returnOrderProcessing->setMetadata([
            'request_id' => 'req-return-20240115-001',
            'refund_order_no' => 'RF202401150001',
            'reason' => '用户申请退款',
            'operator' => 'admin'
        ]);
        $manager->persist($returnOrderProcessing);
        $this->addReference(self::RETURN_ORDER_PROCESSING_REFERENCE, $returnOrderProcessing);

        // 创建成功的分账回退单
        $returnOrderSuccess = new ProfitShareReturnOrder();
        $returnOrderSuccess->setMerchant($testMerchant);
        $returnOrderSuccess->setSubMchId('1900000109');
        $returnOrderSuccess->setOrderId('3008450740201411110007820471');
        $returnOrderSuccess->setOutOrderNo('P202401140001');
        $returnOrderSuccess->setOutReturnNo('R202401140001');
        $returnOrderSuccess->setReturnNo('50000000000000000000000000000001');
        $returnOrderSuccess->setAmount(500);
        $returnOrderSuccess->setDescription('订单退款，分账金额回退');
        $returnOrderSuccess->setResult('SUCCESS');
        $returnOrderSuccess->setWechatCreatedAt(new \DateTimeImmutable('2024-01-14 15:30:00'));
        $returnOrderSuccess->setWechatFinishedAt(new \DateTimeImmutable('2024-01-14 15:30:45'));
        $returnOrderSuccess->setRequestPayload(json_encode([
            'sub_mch_id' => '1900000109',
            'order_id' => '3008450740201411110007820471',
            'out_order_no' => 'P202401140001',
            'out_return_no' => 'R202401140001',
            'return_mch_id' => '1900000109',
            'amount' => 500,
            'description' => '订单退款，分账金额回退'
        ], JSON_THROW_ON_ERROR));
        $returnOrderSuccess->setResponsePayload(json_encode([
            'sub_mch_id' => '1900000109',
            'return_no' => '50000000000000000000000000000001',
            'out_order_no' => 'P202401140001',
            'out_return_no' => 'R202401140001',
            'order_id' => '3008450740201411110007820471',
            'state' => 'SUCCESS',
            'amount' => 500,
            'return_account' => '1900000109',
            'return_amount' => 500,
            'fail_reason' => null
        ], JSON_THROW_ON_ERROR));
        $returnOrderSuccess->setMetadata([
            'request_id' => 'req-return-20240114-001',
            'refund_order_no' => 'RF202401140001',
            'reason' => '订单退款',
            'operator' => 'admin',
            'settlement_time' => '2024-01-14 15:30:45',
            'actual_return_amount' => 500
        ]);
        $manager->persist($returnOrderSuccess);
        $this->addReference(self::RETURN_ORDER_SUCCESS_REFERENCE, $returnOrderSuccess);

        // 创建失败的分账回退单
        $returnOrderFailed = new ProfitShareReturnOrder();
        $returnOrderFailed->setMerchant($testMerchant);
        $returnOrderFailed->setSubMchId('1900000109');
        $returnOrderFailed->setOrderId('3008450740201411110007820470');
        $returnOrderFailed->setOutOrderNo('P202401130001');
        $returnOrderFailed->setOutReturnNo('R202401130001');
        $returnOrderFailed->setAmount(200);
        $returnOrderFailed->setDescription('测试回退失败场景');
        $returnOrderFailed->setResult('FAILED');
        $returnOrderFailed->setFailReason('ACCOUNT_ABNORMAL');
        $returnOrderFailed->setWechatCreatedAt(new \DateTimeImmutable('2024-01-13 11:20:00'));
        $returnOrderFailed->setWechatFinishedAt(new \DateTimeImmutable('2024-01-13 11:20:30'));
        $returnOrderFailed->setRequestPayload(json_encode([
            'sub_mch_id' => '1900000109',
            'order_id' => '3008450740201411110007820470',
            'out_order_no' => 'P202401130001',
            'out_return_no' => 'R202401130001',
            'return_mch_id' => '1900000111',
            'amount' => 200,
            'description' => '测试回退失败场景'
        ], JSON_THROW_ON_ERROR));
        $returnOrderFailed->setResponsePayload(json_encode([
            'sub_mch_id' => '1900000109',
            'out_order_no' => 'P202401130001',
            'out_return_no' => 'R202401130001',
            'order_id' => '3008450740201411110007820470',
            'state' => 'FAILED',
            'amount' => 200,
            'return_account' => '1900000111',
            'return_amount' => 0,
            'fail_reason' => 'ACCOUNT_ABNORMAL',
            'fail_code' => 'PARAM_ERROR'
        ], JSON_THROW_ON_ERROR));
        $returnOrderFailed->setMetadata([
            'request_id' => 'req-return-20240113-001',
            'reason' => '测试场景',
            'operator' => 'admin',
            'error_code' => 'ACCOUNT_ABNORMAL',
            'error_message' => '回退商户账户异常',
            'retry_count' => 2,
            'last_retry_at' => '2024-01-13 11:25:00'
        ]);
        $manager->persist($returnOrderFailed);
        $this->addReference(self::RETURN_ORDER_FAILED_REFERENCE, $returnOrderFailed);

        // 创建部分成功的分账回退单
        $returnOrderPartial = new ProfitShareReturnOrder();
        $returnOrderPartial->setMerchant($testMerchant);
        $returnOrderPartial->setSubMchId('1900000109');
        $returnOrderPartial->setOrderId('3008450740201411110007820469');
        $returnOrderPartial->setOutOrderNo('P202401120001');
        $returnOrderPartial->setOutReturnNo('R202401120001');
        $returnOrderPartial->setReturnNo('50000000000000000000000000000002');
        $returnOrderPartial->setAmount(1000);
        $returnOrderPartial->setDescription('部分回退测试');
        $returnOrderPartial->setResult('SUCCESS');
        $returnOrderPartial->setFailReason('INSUFFICIENT_BALANCE');
        $returnOrderPartial->setWechatCreatedAt(new \DateTimeImmutable('2024-01-12 16:45:00'));
        $returnOrderPartial->setWechatFinishedAt(new \DateTimeImmutable('2024-01-12 16:46:00'));
        $returnOrderPartial->setRequestPayload(json_encode([
            'sub_mch_id' => '1900000109',
            'order_id' => '3008450740201411110007820469',
            'out_order_no' => 'P202401120001',
            'out_return_no' => 'R202401120001',
            'return_mch_id' => '1900000109',
            'amount' => 1000,
            'description' => '部分回退测试'
        ], JSON_THROW_ON_ERROR));
        $returnOrderPartial->setResponsePayload(json_encode([
            'sub_mch_id' => '1900000109',
            'return_no' => '50000000000000000000000000000002',
            'out_order_no' => 'P202401120001',
            'out_return_no' => 'R202401120001',
            'order_id' => '3008450740201411110007820469',
            'state' => 'SUCCESS',
            'amount' => 1000,
            'return_account' => '1900000109',
            'return_amount' => 800,
            'fail_reason' => 'INSUFFICIENT_BALANCE',
            'unsplit_amount' => 200
        ], JSON_THROW_ON_ERROR));
        $returnOrderPartial->setMetadata([
            'request_id' => 'req-return-20240112-001',
            'reason' => '部分回退测试',
            'operator' => 'admin',
            'actual_return_amount' => 800,
            'unsplit_amount' => 200,
            'settlement_time' => '2024-01-12 16:46:00'
        ]);
        $manager->persist($returnOrderPartial);

        $manager->flush();
    }
}