<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Service;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReceiver;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOrderState;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareReceiverResult;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareResponseProcessor;
use WechatPayBundle\Entity\Merchant;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareResponseProcessor::class)]
final class ProfitShareResponseProcessorTest extends AbstractIntegrationTestCase
{
    private ProfitShareResponseProcessor $processor;

    protected function onSetUp(): void
    {
        $this->processor = self::getService(ProfitShareResponseProcessor::class);
    }

    public function testApplyResponseWithProcessingOrder(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_id');

        $order = new ProfitShareOrder();
        $order->setMerchant($merchant);
        $order->setSubMchId('test_sub_mch_id');

        $response = [
            'state' => 'PROCESSING',
            'transaction_id' => 'test_transaction_id',
            'order_id' => 'test_order_id',
            'out_order_no' => 'test_out_order_no',
        ];

        $this->processor->applyResponse($order, $response);

        $this->assertSame(ProfitShareOrderState::PROCESSING, $order->getState());
        $this->assertSame('test_transaction_id', $order->getTransactionId());
        $this->assertSame('test_order_id', $order->getOrderId());
        $this->assertSame('test_out_order_no', $order->getOutOrderNo());
    }

    public function testApplyResponseWithFinishedOrder(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_id');

        $order = new ProfitShareOrder();
        $order->setMerchant($merchant);
        $order->setSubMchId('test_sub_mch_id');

        $response = [
            'state' => 'FINISHED',
        ];

        $this->processor->applyResponse($order, $response);

        $this->assertSame(ProfitShareOrderState::FINISHED, $order->getState());
    }

    public function testApplyResponseWithClosedOrder(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_id');

        $order = new ProfitShareOrder();
        $order->setMerchant($merchant);
        $order->setSubMchId('test_sub_mch_id');

        $response = [
            'state' => 'CLOSED',
        ];

        $this->processor->applyResponse($order, $response);

        $this->assertSame(ProfitShareOrderState::CLOSED, $order->getState());
    }

    public function testApplyResponseWithSuccessfulReceivers(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_id');

        $receiver = new ProfitShareReceiver();
        $receiver->setType('MERCHANT_ID');
        $receiver->setAccount('test_account');
        $receiver->setAmount(100);
        $receiver->setSequence(0);

        $order = new ProfitShareOrder();
        $order->setMerchant($merchant);
        $order->setSubMchId('test_sub_mch_id');
        $order->addReceiver($receiver);

        $response = [
            'state' => 'FINISHED',
            'receivers' => [
                [
                    'type' => 'MERCHANT_ID',
                    'account' => 'test_account',
                    'amount' => 100,
                    'result' => 'SUCCESS',
                    'finish_time' => '2023-01-01T12:00:00+08:00',
                ],
            ],
        ];

        $this->processor->applyResponse($order, $response);

        $this->assertSame(ProfitShareOrderState::FINISHED, $order->getState());
        $this->assertSame(ProfitShareReceiverResult::SUCCESS, $receiver->getResult());
        $this->assertSame(100, $receiver->getFinishAmount());
        $this->assertSame('2023-01-01T12:00:00+08:00', $order->getFinishTime());
        $this->assertSame('2023-01-01T12:00:00+08:00', $order->getSuccessTime());
    }

    public function testApplyResponseWithFailedReceivers(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_id');

        $receiver = new ProfitShareReceiver();
        $receiver->setType('MERCHANT_ID');
        $receiver->setAccount('test_account');
        $receiver->setAmount(100);
        $receiver->setSequence(0);

        $order = new ProfitShareOrder();
        $order->setMerchant($merchant);
        $order->setSubMchId('test_sub_mch_id');
        $order->addReceiver($receiver);

        $response = [
            'receivers' => [
                [
                    'type' => 'MERCHANT_ID',
                    'account' => 'test_account',
                    'amount' => 100,
                    'result' => 'FAILED',
                    'finish_time' => '2023-01-01T12:00:00+08:00',
                ],
            ],
        ];

        $this->processor->applyResponse($order, $response);

        $this->assertSame(ProfitShareReceiverResult::FAILED, $receiver->getResult());
        $this->assertSame('2023-01-01T12:00:00+08:00', $order->getFinishTime());
        // Success time should not be set for failed receivers
        $this->assertNull($order->getSuccessTime());
    }

    public function testApplyResponseWithInvalidReceivers(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_id');

        $order = new ProfitShareOrder();
        $order->setMerchant($merchant);
        $order->setSubMchId('test_sub_mch_id');

        $response = [
            'receivers' => [
                [
                    'type' => 'MERCHANT_ID',
                    // Missing required fields
                ],
            ],
        ];

        // Should handle invalid receivers gracefully
        $this->processor->applyResponse($order, $response);

        // Should not set finish time for invalid receivers
        $this->assertNull($order->getFinishTime());
    }

    public function testApplyResponseWithEmptyResponse(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_id');

        $order = new ProfitShareOrder();
        $order->setMerchant($merchant);
        $order->setSubMchId('test_sub_mch_id');

        // Should handle empty response gracefully
        $this->processor->applyResponse($order, []);

        // State should remain at default PROCESSING value (entity has default state)
        $this->assertSame(ProfitShareOrderState::PROCESSING, $order->getState());
    }

    public function testApplyResponseWithMultipleFinishTimes(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_id');

        $receiver = new ProfitShareReceiver();
        $receiver->setType('MERCHANT_ID');
        $receiver->setAccount('test_account');
        $receiver->setAmount(100);
        $receiver->setSequence(0);

        $order = new ProfitShareOrder();
        $order->setMerchant($merchant);
        $order->setSubMchId('test_sub_mch_id');
        $order->addReceiver($receiver);

        $response = [
            'receivers' => [
                [
                    'type' => 'MERCHANT_ID',
                    'account' => 'test_account',
                    'amount' => 100,
                    'result' => 'SUCCESS',
                    'finish_time' => '2023-01-01T12:00:00+08:00',
                ],
                [
                    'type' => 'PERSONAL_OPENID',
                    'account' => 'test_openid',
                    'amount' => 200,
                    'result' => 'SUCCESS',
                    'finish_time' => '2023-01-01T13:00:00+08:00',
                ],
            ],
        ];

        $this->processor->applyResponse($order, $response);

        // Max date
        $this->assertSame('2023-01-01T13:00:00+08:00', $order->getFinishTime());
        // Min date
        $this->assertSame('2023-01-01T12:00:00+08:00', $order->getSuccessTime());
    }

    public function testServiceIsRegisteredInContainer(): void
    {
        $processor = self::getService(ProfitShareResponseProcessor::class);
        $this->assertInstanceOf(ProfitShareResponseProcessor::class, $processor);
    }
}
