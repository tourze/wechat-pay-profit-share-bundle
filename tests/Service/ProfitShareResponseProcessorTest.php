<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReceiver;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOrderState;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareReceiverResult;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareResponseProcessor;

/**
 * @internal
 */
#[CoversClass(ProfitShareResponseProcessor::class)]
class ProfitShareResponseProcessorTest extends TestCase
{
    private ProfitShareResponseProcessor $processor;

    protected function onSetUp(): void
    {
        $this->processor = new ProfitShareResponseProcessor();
    }

    public function testApplyResponseWithProcessingOrder(): void
    {
        $order = $this->createMock(ProfitShareOrder::class);
        $response = [
            'state' => 'PROCESSING',
            'transaction_id' => 'test_transaction_id',
            'order_id' => 'test_order_id',
            'out_order_no' => 'test_out_order_no',
        ];

        $order->expects($this->once())
            ->method('setState')
            ->with(ProfitShareOrderState::PROCESSING)
        ;

        $order->expects($this->once())
            ->method('setTransactionId')
            ->with('test_transaction_id')
        ;

        $order->expects($this->once())
            ->method('setOrderId')
            ->with('test_order_id')
        ;

        $order->expects($this->once())
            ->method('setOutOrderNo')
            ->with('test_out_order_no')
        ;

        $order->expects($this->once())
            ->method('getReceivers')
            ->willReturn([])
        ;

        $this->processor->applyResponse($order, $response);
    }

    public function testApplyResponseWithFinishedOrder(): void
    {
        $order = $this->createMock(ProfitShareOrder::class);
        $response = [
            'state' => 'FINISHED',
        ];

        $order->expects($this->once())
            ->method('setState')
            ->with(ProfitShareOrderState::FINISHED)
        ;

        $order->expects($this->never())
            ->method('setTransactionId')
        ;

        $order->expects($this->once())
            ->method('setOrderId')
            ->with('')
        ;

        $order->expects($this->once())
            ->method('setOutOrderNo')
            ->with('')
        ;

        $order->expects($this->once())
            ->method('getReceivers')
            ->willReturn([])
        ;

        $this->processor->applyResponse($order, $response);
    }

    public function testApplyResponseWithClosedOrder(): void
    {
        $order = $this->createMock(ProfitShareOrder::class);
        $response = [
            'state' => 'CLOSED',
        ];

        $order->expects($this->once())
            ->method('setState')
            ->with(ProfitShareOrderState::CLOSED)
        ;

        $order->expects($this->once())
            ->method('getReceivers')
            ->willReturn([])
        ;

        $this->processor->applyResponse($order, $response);
    }

    public function testApplyResponseWithSuccessfulReceivers(): void
    {
        $receiver = $this->createMock(ProfitShareReceiver::class);
        $order = $this->createMock(ProfitShareOrder::class);

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

        $order->expects($this->once())
            ->method('setState')
            ->with(ProfitShareOrderState::FINISHED)
        ;

        $order->expects($this->once())
            ->method('getReceivers')
            ->willReturn([$receiver])
        ;

        $receiver->expects($this->once())
            ->method('getType')
            ->willReturn('MERCHANT_ID')
        ;

        $receiver->expects($this->once())
            ->method('getAccount')
            ->willReturn('test_account')
        ;

        $receiver->expects($this->once())
            ->method('getAmount')
            ->willReturn(100)
        ;

        $receiver->expects($this->once())
            ->method('setDetail')
            ->with(json_encode($response['receivers'][0], JSON_UNESCAPED_UNICODE))
        ;

        $receiver->expects($this->once())
            ->method('setResult')
            ->with(ProfitShareReceiverResult::SUCCESS)
        ;

        $receiver->expects($this->once())
            ->method('setFinishAmount')
            ->with(100)
        ;

        $order->expects($this->once())
            ->method('setFinishTime')
            ->with('2023-01-01T12:00:00+08:00')
        ;

        $order->expects($this->once())
            ->method('setSuccessTime')
            ->with('2023-01-01T12:00:00+08:00')
        ;

        $this->processor->applyResponse($order, $response);
    }

    public function testApplyResponseWithFailedReceivers(): void
    {
        $receiver = $this->createMock(ProfitShareReceiver::class);
        $order = $this->createMock(ProfitShareOrder::class);

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

        $order->expects($this->once())
            ->method('getReceivers')
            ->willReturn([$receiver])
        ;

        $receiver->expects($this->once())
            ->method('getType')
            ->willReturn('MERCHANT_ID')
        ;

        $receiver->expects($this->once())
            ->method('getAccount')
            ->willReturn('test_account')
        ;

        $receiver->expects($this->once())
            ->method('getAmount')
            ->willReturn(100)
        ;

        $receiver->expects($this->once())
            ->method('setResult')
            ->with(ProfitShareReceiverResult::FAILED)
        ;

        $order->expects($this->once())
            ->method('setFinishTime')
            ->with('2023-01-01T12:00:00+08:00')
        ;

        $order->expects($this->never())
            ->method('setSuccessTime')
        ;

        $this->processor->applyResponse($order, $response);
    }

    public function testApplyResponseWithInvalidReceivers(): void
    {
        $order = $this->createMock(ProfitShareOrder::class);

        $response = [
            'receivers' => [
                [
                    'type' => 'MERCHANT_ID',
                    // Missing required fields
                ],
            ],
        ];

        $order->expects($this->once())
            ->method('getReceivers')
            ->willReturn([])
        ;

        // Should not process invalid receivers
        $order->expects($this->never())
            ->method('setFinishTime')
        ;

        $this->processor->applyResponse($order, $response);
    }

    public function testApplyResponseWithNullResponse(): void
    {
        $order = $this->createMock(ProfitShareOrder::class);

        // Should handle empty or null response gracefully
        $order->expects($this->once())
            ->method('getReceivers')
            ->willReturn([])
        ;

        $this->processor->applyResponse($order, []);
    }

    public function testApplyResponseWithMultipleFinishTimes(): void
    {
        $receiver = $this->createMock(ProfitShareReceiver::class);
        $order = $this->createMock(ProfitShareOrder::class);

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

        $order->expects($this->once())
            ->method('getReceivers')
            ->willReturn([$receiver])
        ;

        $order->expects($this->once())
            ->method('setFinishTime')
            ->with('2023-01-01T13:00:00+08:00'); // Max date

        $order->expects($this->once())
            ->method('setSuccessTime')
            ->with('2023-01-01T12:00:00+08:00'); // Min date

        $this->processor->applyResponse($order, $response);
    }
}
