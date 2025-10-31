<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareOrderRequest;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReceiverRequest;

#[CoversClass(ProfitShareOrderRequest::class)]
class ProfitShareOrderRequestTest extends TestCase
{
    public function testCreateRequestWithRequiredFields(): void
    {
        $request = new ProfitShareOrderRequest(
            subMchId: '1900000109',
            transactionId: '4200000452202312011876781234',
            outOrderNo: 'P20150806125346'
        );

        $this->assertSame('1900000109', $request->getSubMchId());
        $this->assertSame('4200000452202312011876781234', $request->getTransactionId());
        $this->assertSame('P20150806125346', $request->getOutOrderNo());
        $this->assertNull($request->getAppId());
        $this->assertNull($request->getSubAppId());
        $this->assertFalse($request->isUnfreezeUnsplit());
        $this->assertEmpty($request->getReceivers());

        $payload = $request->toPayload();
        $expectedPayload = [
            'sub_mchid' => '1900000109',
            'transaction_id' => '4200000452202312011876781234',
            'out_order_no' => 'P20150806125346',
            'unfreeze_unsplit' => false,
            'receivers' => [],
        ];
        $this->assertSame($expectedPayload, $payload);
    }

    public function testSetAndGetAppId(): void
    {
        $request = new ProfitShareOrderRequest(
            subMchId: '1900000109',
            transactionId: '4200000452202312011876781234',
            outOrderNo: 'P20150806125346'
        );

        $request->setAppId('wxd678efh567hg6992');
        $this->assertSame('wxd678efh567hg6992', $request->getAppId());

        $payload = $request->toPayload();
        $this->assertArrayHasKey('appid', $payload);
        $this->assertSame('wxd678efh567hg6992', $payload['appid']);
    }

    public function testSetAndGetSubAppId(): void
    {
        $request = new ProfitShareOrderRequest(
            subMchId: '1900000109',
            transactionId: '4200000452202312011876781234',
            outOrderNo: 'P20150806125346'
        );

        $request->setSubAppId('wx1234567890abcdef');
        $this->assertSame('wx1234567890abcdef', $request->getSubAppId());

        $payload = $request->toPayload();
        $this->assertArrayHasKey('sub_appid', $payload);
        $this->assertSame('wx1234567890abcdef', $payload['sub_appid']);
    }

    public function testSetAndGetUnfreezeUnsplit(): void
    {
        $request = new ProfitShareOrderRequest(
            subMchId: '1900000109',
            transactionId: '4200000452202312011876781234',
            outOrderNo: 'P20150806125346'
        );

        $this->assertFalse($request->isUnfreezeUnsplit());

        $request->setUnfreezeUnsplit(true);
        $this->assertTrue($request->isUnfreezeUnsplit());

        $payload = $request->toPayload();
        $this->assertTrue($payload['unfreeze_unsplit']);
    }

    public function testAddAndGetReceivers(): void
    {
        $request = new ProfitShareOrderRequest(
            subMchId: '1900000109',
            transactionId: '4200000452202312011876781234',
            outOrderNo: 'P20150806125346'
        );

        $receiver1 = new ProfitShareReceiverRequest(
            type: 'MERCHANT_ID',
            account: '1900000109',
            amount: 100,
            description: '分账给商户'
        );

        $receiver2 = new ProfitShareReceiverRequest(
            type: 'PERSONAL_OPENID',
            account: 'oxTWIuGaIt6gTKsQRLau2M0yL16E',
            amount: 50,
            description: '分账给个人',
            name: '张三'
        );

        $request->addReceiver($receiver1);
        $request->addReceiver($receiver2);

        $receivers = $request->getReceivers();
        $this->assertCount(2, $receivers);
        $this->assertSame($receiver1, $receivers[0]);
        $this->assertSame($receiver2, $receivers[1]);

        $payload = $request->toPayload();
        $this->assertCount(2, $payload['receivers']);
        $this->assertSame([
            'type' => 'MERCHANT_ID',
            'account' => '1900000109',
            'amount' => 100,
            'description' => '分账给商户',
        ], $payload['receivers'][0]);
        $this->assertSame([
            'type' => 'PERSONAL_OPENID',
            'account' => 'oxTWIuGaIt6gTKsQRLau2M0yL16E',
            'amount' => 50,
            'description' => '分账给个人',
            'name' => '张三',
        ], $payload['receivers'][1]);
    }

    public function testSetReceivers(): void
    {
        $request = new ProfitShareOrderRequest(
            subMchId: '1900000109',
            transactionId: '4200000452202312011876781234',
            outOrderNo: 'P20150806125346'
        );

        $receiver = new ProfitShareReceiverRequest(
            type: 'MERCHANT_ID',
            account: '1900000109',
            amount: 100,
            description: '分账给商户'
        );

        $receivers = [$receiver];
        $request->setReceivers($receivers);

        $this->assertSame($receivers, $request->getReceivers());
        $this->assertCount(1, $request->getReceivers());
    }

    public function testToPayloadWithAllFields(): void
    {
        $request = new ProfitShareOrderRequest(
            subMchId: '1900000109',
            transactionId: '4200000452202312011876781234',
            outOrderNo: 'P20150806125346'
        );

        $request->setAppId('wxd678efh567hg6992');
        $request->setSubAppId('wx1234567890abcdef');
        $request->setUnfreezeUnsplit(true);

        $receiver = new ProfitShareReceiverRequest(
            type: 'MERCHANT_ID',
            account: '1900000109',
            amount: 100,
            description: '分账给商户'
        );
        $request->addReceiver($receiver);

        $payload = $request->toPayload();
        $expectedPayload = [
            'sub_mchid' => '1900000109',
            'transaction_id' => '4200000452202312011876781234',
            'out_order_no' => 'P20150806125346',
            'unfreeze_unsplit' => true,
            'receivers' => [[
                'type' => 'MERCHANT_ID',
                'account' => '1900000109',
                'amount' => 100,
                'description' => '分账给商户',
            ]],
            'appid' => 'wxd678efh567hg6992',
            'sub_appid' => 'wx1234567890abcdef',
        ];
        $this->assertSame($expectedPayload, $payload);
    }

    public function testToPayloadExcludesEmptyOptionalFields(): void
    {
        $request = new ProfitShareOrderRequest(
            subMchId: '1900000109',
            transactionId: '4200000452202312011876781234',
            outOrderNo: 'P20150806125346'
        );

        $request->setAppId('');
        $request->setSubAppId('');

        $payload = $request->toPayload();

        // Required fields should be present
        $this->assertArrayHasKey('sub_mchid', $payload);
        $this->assertArrayHasKey('transaction_id', $payload);
        $this->assertArrayHasKey('out_order_no', $payload);
        $this->assertArrayHasKey('unfreeze_unsplit', $payload);
        $this->assertArrayHasKey('receivers', $payload);

        // Empty optional fields should be excluded
        $this->assertArrayNotHasKey('appid', $payload);
        $this->assertArrayNotHasKey('sub_appid', $payload);
    }

    public function testEmptyReceiversArray(): void
    {
        $request = new ProfitShareOrderRequest(
            subMchId: '1900000109',
            transactionId: '4200000452202312011876781234',
            outOrderNo: 'P20150806125346'
        );

        $payload = $request->toPayload();
        $this->assertSame([], $payload['receivers']);
    }
}