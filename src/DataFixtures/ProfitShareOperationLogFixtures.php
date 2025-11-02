<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOperationLog;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOperationType;
use WechatPayBundle\DataFixtures\MerchantFixtures;
use WechatPayBundle\Entity\Merchant;

/**
 * 微信支付分账操作日志数据填充
 * 创建测试用的分账操作日志数据
 */
#[When(env: 'test')]
#[When(env: 'dev')]
class ProfitShareOperationLogFixtures extends Fixture implements FixtureGroupInterface
{
    public const OPERATION_LOG_ORDER_SUCCESS_REFERENCE = 'operation-log-order-success';
    public const OPERATION_LOG_RECEIVER_ADD_SUCCESS_REFERENCE = 'operation-log-receiver-add-success';
    public const OPERATION_LOG_BILL_APPLY_SUCCESS_REFERENCE = 'operation-log-bill-apply-success';
    public const OPERATION_LOG_ORDER_FAILED_REFERENCE = 'operation-log-order-failed';

    public static function getGroups(): array
    {
        return ['test', 'dev'];
    }

    public function load(ObjectManager $manager): void
    {
        // 获取测试商户
        /** @var Merchant $testMerchant */
        $testMerchant = $this->getReference(MerchantFixtures::TEST_MERCHANT_REFERENCE, Merchant::class);

        // 创建成功的分账请求操作日志
        $operationLogOrderSuccess = new ProfitShareOperationLog();
        $operationLogOrderSuccess->setMerchant($testMerchant);
        $operationLogOrderSuccess->setSubMchId('1900000109');
        $operationLogOrderSuccess->setType(ProfitShareOperationType::REQUEST_ORDER);
        $operationLogOrderSuccess->setSuccess(true);
        $operationLogOrderSuccess->setRequestPayload(json_encode([
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
        $operationLogOrderSuccess->setResponsePayload(json_encode([
            'sub_mch_id' => '1900000109',
            'transaction_id' => '4200000452202401158754321234',
            'order_id' => '3008450740201411110007820472',
            'out_order_no' => 'P202401150001',
            'state' => 'PROCESSING',
        ], JSON_THROW_ON_ERROR));
        $operationLogOrderSuccess->setMetadata([
            'request_id' => 'req-20240115-001',
            'processing_time' => 0.245,
            'api_version' => 'v3',
        ]);
        $manager->persist($operationLogOrderSuccess);
        $this->addReference(self::OPERATION_LOG_ORDER_SUCCESS_REFERENCE, $operationLogOrderSuccess);

        // 创建成功添加分账接收方的操作日志
        $operationLogReceiverAdd = new ProfitShareOperationLog();
        $operationLogReceiverAdd->setMerchant($testMerchant);
        $operationLogReceiverAdd->setSubMchId('1900000109');
        $operationLogReceiverAdd->setType(ProfitShareOperationType::ADD_RECEIVER);
        $operationLogReceiverAdd->setSuccess(true);
        $operationLogReceiverAdd->setRequestPayload(json_encode([
            'sub_mch_id' => '1900000109',
            'receiver' => [
                'type' => 'PERSONAL_OPENID',
                'account' => 'oxRHG5p6J9nW1z2y3x4w5v6u7t8',
                'relation_type' => 'DISTRIBUTOR',
            ],
        ], JSON_THROW_ON_ERROR));
        $operationLogReceiverAdd->setResponsePayload(json_encode([
            'sub_mch_id' => '1900000109',
            'result_code' => 'SUCCESS',
        ], JSON_THROW_ON_ERROR));
        $operationLogReceiverAdd->setMetadata([
            'request_id' => 'req-20240115-002',
            'processing_time' => 0.156,
            'receiver_type' => 'PERSONAL_OPENID',
        ]);
        $manager->persist($operationLogReceiverAdd);
        $this->addReference(self::OPERATION_LOG_RECEIVER_ADD_SUCCESS_REFERENCE, $operationLogReceiverAdd);

        // 创建成功申请账单的操作日志
        $operationLogBillApply = new ProfitShareOperationLog();
        $operationLogBillApply->setMerchant($testMerchant);
        $operationLogBillApply->setSubMchId('1900000109');
        $operationLogBillApply->setType(ProfitShareOperationType::APPLY_BILL);
        $operationLogBillApply->setSuccess(true);
        $operationLogBillApply->setRequestPayload(json_encode([
            'sub_mch_id' => '1900000109',
            'bill_date' => '20240114',
            'tar_type' => 'GZIP',
        ], JSON_THROW_ON_ERROR));
        $operationLogBillApply->setResponsePayload(json_encode([
            'hash_type' => 'SHA1',
            'hash_value' => 'e1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p7q8r9s0',
            'download_url' => 'https://api.mch.weixin.qq.com/v3/profitsharing/bills?file_token=xxxxx',
        ], JSON_THROW_ON_ERROR));
        $operationLogBillApply->setMetadata([
            'request_id' => 'req-20240115-003',
            'processing_time' => 1.234,
            'bill_date' => '20240114',
        ]);
        $manager->persist($operationLogBillApply);
        $this->addReference(self::OPERATION_LOG_BILL_APPLY_SUCCESS_REFERENCE, $operationLogBillApply);

        // 创建失败的分账请求操作日志
        $operationLogOrderFailed = new ProfitShareOperationLog();
        $operationLogOrderFailed->setMerchant($testMerchant);
        $operationLogOrderFailed->setSubMchId('1900000109');
        $operationLogOrderFailed->setType(ProfitShareOperationType::REQUEST_ORDER);
        $operationLogOrderFailed->setSuccess(false);
        $operationLogOrderFailed->setErrorCode('INVALID_REQUEST');
        $operationLogOrderFailed->setErrorMessage('分账订单不存在');
        $operationLogOrderFailed->setRequestPayload(json_encode([
            'sub_mch_id' => '1900000109',
            'transaction_id' => '4200000452202401150000000000',
            'out_order_no' => 'P202401150002',
            'receivers' => [
                [
                    'type' => 'MERCHANT_ID',
                    'account' => '1900000109',
                    'amount' => 200,
                    'description' => '分给商户',
                ],
            ],
        ], JSON_THROW_ON_ERROR));
        $operationLogOrderFailed->setResponsePayload(json_encode([
            'code' => 'INVALID_REQUEST',
            'message' => '分账订单不存在',
        ], JSON_THROW_ON_ERROR));
        $operationLogOrderFailed->setMetadata([
            'request_id' => 'req-20240115-004',
            'processing_time' => 0.089,
            'error_category' => 'business_error',
            'retryable' => false,
        ]);
        $manager->persist($operationLogOrderFailed);
        $this->addReference(self::OPERATION_LOG_ORDER_FAILED_REFERENCE, $operationLogOrderFailed);

        // 创建查询分账结果的操作日志
        $operationLogQuery = new ProfitShareOperationLog();
        $operationLogQuery->setMerchant($testMerchant);
        $operationLogQuery->setSubMchId('1900000109');
        $operationLogQuery->setType(ProfitShareOperationType::QUERY_ORDER);
        $operationLogQuery->setSuccess(true);
        $operationLogQuery->setRequestPayload(json_encode([
            'sub_mch_id' => '1900000109',
            'transaction_id' => '4200000452202401158754321234',
        ], JSON_THROW_ON_ERROR));
        $operationLogQuery->setResponsePayload(json_encode([
            'sub_mch_id' => '1900000109',
            'transaction_id' => '4200000452202401158754321234',
            'order_id' => '3008450740201411110007820472',
            'out_order_no' => 'P202401150001',
            'state' => 'FINISHED',
            'receivers' => [
                [
                    'type' => 'MERCHANT_ID',
                    'account' => '1900000109',
                    'amount' => 100,
                    'description' => '分给商户',
                    'result' => 'SUCCESS',
                ],
            ],
        ], JSON_THROW_ON_ERROR));
        $operationLogQuery->setMetadata([
            'request_id' => 'req-20240115-005',
            'processing_time' => 0.167,
            'order_state' => 'FINISHED',
        ]);
        $manager->persist($operationLogQuery);

        // 创建分账通知操作日志
        $operationLogNotification = new ProfitShareOperationLog();
        $operationLogNotification->setMerchant($testMerchant);
        $operationLogNotification->setSubMchId('1900000109');
        $operationLogNotification->setType(ProfitShareOperationType::NOTIFICATION);
        $operationLogNotification->setSuccess(true);
        $operationLogNotification->setRequestPayload(json_encode([
            'event_type' => 'TRANSACTION.PROFITSHARING.FINISH',
            'resource_type' => 'encrypt-resource',
            'resource' => [
                'original_type' => 'mchtrade',
                'algorithm' => 'AEAD_AES_256_GCM',
                'ciphertext' => '...',
                'associated_data' => 'profitsharing',
                'nonce' => '...',
            ],
        ], JSON_THROW_ON_ERROR));
        $operationLogNotification->setResponsePayload(json_encode([
            'code' => 'SUCCESS',
            'message' => '成功',
        ], JSON_THROW_ON_ERROR));
        $operationLogNotification->setMetadata([
            'notification_id' => 'notif-20240115-001',
            'event_type' => 'TRANSACTION.PROFITSHARING.FINISH',
            'processing_time' => 0.034,
        ]);
        $manager->persist($operationLogNotification);

        $manager->flush();
    }
}
