<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReceiver;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareOrderRequest;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReceiverRequest;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareOrderFactory;
use WechatPayBundle\Entity\Merchant;

#[CoversClass(ProfitShareOrderFactory::class)]
class ProfitShareOrderFactoryTest extends TestCase
{
    private ProfitShareOrderFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ProfitShareOrderFactory();
    }

    public function testBuildOrderEntity(): void
    {
        $merchant = $this->createMock(Merchant::class);
        $receiverRequest = $this->createMock(ProfitShareReceiverRequest::class);
        $orderRequest = $this->createMock(ProfitShareOrderRequest::class);

        $receiverRequest->method('getType')->willReturn('MERCHANT_ID');
        $receiverRequest->method('getAccount')->willReturn('test_account');
        $receiverRequest->method('getAmount')->willReturn(100);
        $receiverRequest->method('getDescription')->willReturn('Test receiver');
        $receiverRequest->method('getName')->willReturn('Test Name');

        $orderRequest->method('getSubMchId')->willReturn('test_sub_mch_id');
        $orderRequest->method('getAppId')->willReturn('test_app_id');
        $orderRequest->method('getSubAppId')->willReturn('test_sub_app_id');
        $orderRequest->method('getTransactionId')->willReturn('test_transaction_id');
        $orderRequest->method('getOutOrderNo')->willReturn('test_out_order_no');
        $orderRequest->method('isUnfreezeUnsplit')->willReturn(true);
        $orderRequest->method('getReceivers')->willReturn([$receiverRequest]);

        $order = $this->factory->buildOrderEntity($merchant, $orderRequest);

        $this->assertInstanceOf(ProfitShareOrder::class, $order);
        $this->assertSame($merchant, $order->getMerchant());
        $this->assertSame('test_sub_mch_id', $order->getSubMchId());
        $this->assertSame('test_app_id', $order->getAppId());
        $this->assertSame('test_sub_app_id', $order->getSubAppId());
        $this->assertSame('test_transaction_id', $order->getTransactionId());
        $this->assertSame('test_out_order_no', $order->getOutOrderNo());
        $this->assertTrue($order->isUnfreezeUnsplit());

        $receivers = $order->getReceivers();
        $this->assertCount(1, $receivers);

        $receiver = $receivers->first();
        $this->assertInstanceOf(ProfitShareReceiver::class, $receiver);
        $this->assertSame(0, $receiver->getSequence());
        $this->assertSame('MERCHANT_ID', $receiver->getType());
        $this->assertSame('test_account', $receiver->getAccount());
        $this->assertSame(100, $receiver->getAmount());
        $this->assertSame('Test receiver', $receiver->getDescription());
        $this->assertSame('Test Name', $receiver->getName());
    }

    public function testBuildOrderEntityWithMultipleReceivers(): void
    {
        $merchant = $this->createMock(Merchant::class);
        $receiverRequest1 = $this->createMock(ProfitShareReceiverRequest::class);
        $receiverRequest2 = $this->createMock(ProfitShareReceiverRequest::class);
        $orderRequest = $this->createMock(ProfitShareOrderRequest::class);

        $receiverRequest1->method('getType')->willReturn('MERCHANT_ID');
        $receiverRequest1->method('getAccount')->willReturn('account1');
        $receiverRequest1->method('getAmount')->willReturn(100);
        $receiverRequest1->method('getDescription')->willReturn('Receiver 1');
        $receiverRequest1->method('getName')->willReturn('Name 1');

        $receiverRequest2->method('getType')->willReturn('PERSONAL_OPENID');
        $receiverRequest2->method('getAccount')->willReturn('openid2');
        $receiverRequest2->method('getAmount')->willReturn(200);
        $receiverRequest2->method('getDescription')->willReturn('Receiver 2');
        $receiverRequest2->method('getName')->willReturn('Name 2');

        $orderRequest->method('getSubMchId')->willReturn('test_sub_mch_id');
        $orderRequest->method('getAppId')->willReturn('test_app_id');
        $orderRequest->method('getSubAppId')->willReturn(null);
        $orderRequest->method('getTransactionId')->willReturn('test_transaction_id');
        $orderRequest->method('getOutOrderNo')->willReturn('test_out_order_no');
        $orderRequest->method('isUnfreezeUnsplit')->willReturn(false);
        $orderRequest->method('getReceivers')->willReturn([$receiverRequest1, $receiverRequest2]);

        $order = $this->factory->buildOrderEntity($merchant, $orderRequest);

        $receivers = $order->getReceivers();
        $this->assertCount(2, $receivers);

        $receiverArray = $receivers->toArray();

        $receiver1 = $receiverArray[0];
        $this->assertSame(0, $receiver1->getSequence());
        $this->assertSame('account1', $receiver1->getAccount());

        $receiver2 = $receiverArray[1];
        $this->assertSame(1, $receiver2->getSequence());
        $this->assertSame('openid2', $receiver2->getAccount());
    }

    public function testBuildOrderEntityWithNullOptionalFields(): void
    {
        $merchant = $this->createMock(Merchant::class);
        $orderRequest = $this->createMock(ProfitShareOrderRequest::class);

        $orderRequest->method('getSubMchId')->willReturn('test_sub_mch_id');
        $orderRequest->method('getAppId')->willReturn('test_app_id');
        $orderRequest->method('getSubAppId')->willReturn(null);
        $orderRequest->method('getTransactionId')->willReturn('test_transaction_id');
        $orderRequest->method('getOutOrderNo')->willReturn('test_out_order_no');
        $orderRequest->method('isUnfreezeUnsplit')->willReturn(false);
        $orderRequest->method('getReceivers')->willReturn([]);

        $order = $this->factory->buildOrderEntity($merchant, $orderRequest);

        $this->assertNull($order->getSubAppId());
        $this->assertCount(0, $order->getReceivers());
        $this->assertFalse($order->isUnfreezeUnsplit());
    }
}