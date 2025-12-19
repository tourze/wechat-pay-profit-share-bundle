<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReceiver;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareOrderRequest;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReceiverRequest;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareOrderFactory;
use WechatPayBundle\Entity\Merchant;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ProfitShareOrderFactory::class)]
final class ProfitShareOrderFactoryTest extends AbstractIntegrationTestCase
{
    private ProfitShareOrderFactory $factory;

    protected function onSetUp(): void
    {
        $this->factory = self::getService(ProfitShareOrderFactory::class);
    }

    public function testBuildOrderEntity(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_id');
        $merchant->setCertSerial('test_cert_serial');
        $merchant->setPemKey('test_pem_key');
        $merchant->setPublicKey('test_public_key');
        $merchant->setPublicKeyId('test_public_key_id');

        $orderRequest = new ProfitShareOrderRequest(
            subMchId: 'test_sub_mch_id',
            transactionId: 'test_transaction_id',
            outOrderNo: 'test_out_order_no',
        );
        $orderRequest->setAppId('test_app_id');
        $orderRequest->setSubAppId('test_sub_app_id');
        $orderRequest->setUnfreezeUnsplit(true);
        $orderRequest->addReceiver(new ProfitShareReceiverRequest(
            type: 'MERCHANT_ID',
            account: 'test_account',
            amount: 100,
            description: 'Test receiver',
            name: 'Test Name'
        ));

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
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_id');
        $merchant->setCertSerial('test_cert_serial');
        $merchant->setPemKey('test_pem_key');
        $merchant->setPublicKey('test_public_key');
        $merchant->setPublicKeyId('test_public_key_id');

        $orderRequest = new ProfitShareOrderRequest(
            subMchId: 'test_sub_mch_id',
            transactionId: 'test_transaction_id',
            outOrderNo: 'test_out_order_no',
        );
        $orderRequest->setAppId('test_app_id');
        $orderRequest->setSubAppId(null);
        $orderRequest->setUnfreezeUnsplit(false);
        $orderRequest->addReceiver(new ProfitShareReceiverRequest(
            type: 'MERCHANT_ID',
            account: 'account1',
            amount: 100,
            description: 'Receiver 1',
            name: 'Name 1'
        ));
        $orderRequest->addReceiver(new ProfitShareReceiverRequest(
            type: 'PERSONAL_OPENID',
            account: 'openid2',
            amount: 200,
            description: 'Receiver 2',
            name: 'Name 2'
        ));

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
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_id');
        $merchant->setCertSerial('test_cert_serial');
        $merchant->setPemKey('test_pem_key');
        $merchant->setPublicKey('test_public_key');
        $merchant->setPublicKeyId('test_public_key_id');

        $orderRequest = new ProfitShareOrderRequest(
            subMchId: 'test_sub_mch_id',
            transactionId: 'test_transaction_id',
            outOrderNo: 'test_out_order_no',
        );
        $orderRequest->setAppId('test_app_id');
        $orderRequest->setSubAppId(null);
        $orderRequest->setUnfreezeUnsplit(false);

        $order = $this->factory->buildOrderEntity($merchant, $orderRequest);

        $this->assertNull($order->getSubAppId());
        $this->assertCount(0, $order->getReceivers());
        $this->assertFalse($order->isUnfreezeUnsplit());
    }

    public function testBuildOrderFromResponse(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_id');
        $merchant->setCertSerial('test_cert_serial');
        $merchant->setPemKey('test_pem_key');
        $merchant->setPublicKey('test_public_key');
        $merchant->setPublicKeyId('test_public_key_id');

        $responseData = [
            'sub_mchid' => 'test_sub_mch_id',
            'transaction_id' => 'test_transaction_id',
            'out_order_no' => 'test_out_order_no',
            'appid' => 'test_app_id',
            'sub_appid' => 'test_sub_app_id',
            'order_id' => 'test_order_id',
        ];

        $order = $this->factory->buildOrderFromResponse($merchant, $responseData);

        $this->assertInstanceOf(ProfitShareOrder::class, $order);
        $this->assertSame($merchant, $order->getMerchant());
        $this->assertSame('test_sub_mch_id', $order->getSubMchId());
        $this->assertSame('test_transaction_id', $order->getTransactionId());
        $this->assertSame('test_out_order_no', $order->getOutOrderNo());
        $this->assertSame('test_app_id', $order->getAppId());
        $this->assertSame('test_sub_app_id', $order->getSubAppId());
        $this->assertSame('test_order_id', $order->getOrderId());
    }

    public function testServiceIsRegisteredInContainer(): void
    {
        $factory = self::getService(ProfitShareOrderFactory::class);
        $this->assertInstanceOf(ProfitShareOrderFactory::class, $factory);
    }
}
