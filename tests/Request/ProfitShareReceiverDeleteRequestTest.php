<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReceiverDeleteRequest;

/**
 * @internal
 */
#[CoversClass(ProfitShareReceiverDeleteRequest::class)]
final class ProfitShareReceiverDeleteRequestTest extends TestCase
{
    public function testCreateRequestWithRequiredFields(): void
    {
        $request = new ProfitShareReceiverDeleteRequest(
            subMchId: '1900000109',
            appid: 'wxd678efh567hg6992',
            type: 'MERCHANT_ID',
            account: '1900000109'
        );

        $this->assertSame('1900000109', $request->getSubMchId());
        $this->assertSame('wxd678efh567hg6992', $request->getAppid());
        $this->assertSame('MERCHANT_ID', $request->getType());
        $this->assertSame('1900000109', $request->getAccount());
        $this->assertNull($request->getSubAppid());

        $payload = $request->toPayload();
        $expectedPayload = [
            'sub_mchid' => '1900000109',
            'appid' => 'wxd678efh567hg6992',
            'type' => 'MERCHANT_ID',
            'account' => '1900000109',
        ];
        $this->assertSame($expectedPayload, $payload);
    }

    public function testCreateRequestWithSubAppid(): void
    {
        $request = new ProfitShareReceiverDeleteRequest(
            subMchId: '1900000109',
            appid: 'wxd678efh567hg6992',
            type: 'PERSONAL_OPENID',
            account: 'oxTWIuGaIt6gTKsQRLau2M0yL16E',
            subAppid: 'wx1234567890abcdef'
        );

        $this->assertSame('1900000109', $request->getSubMchId());
        $this->assertSame('wxd678efh567hg6992', $request->getAppid());
        $this->assertSame('PERSONAL_OPENID', $request->getType());
        $this->assertSame('oxTWIuGaIt6gTKsQRLau2M0yL16E', $request->getAccount());
        $this->assertSame('wx1234567890abcdef', $request->getSubAppid());

        $payload = $request->toPayload();
        $expectedPayload = [
            'sub_mchid' => '1900000109',
            'appid' => 'wxd678efh567hg6992',
            'type' => 'PERSONAL_OPENID',
            'account' => 'oxTWIuGaIt6gTKsQRLau2M0yL16E',
            'sub_appid' => 'wx1234567890abcdef',
        ];
        $this->assertSame($expectedPayload, $payload);
    }

    public function testToPayloadExcludesEmptySubAppid(): void
    {
        $request = new ProfitShareReceiverDeleteRequest(
            subMchId: '1900000109',
            appid: 'wxd678efh567hg6992',
            type: 'MERCHANT_ID',
            account: '1900000109',
            subAppid: ''
        );

        $payload = $request->toPayload();

        // Required fields should be present
        $this->assertArrayHasKey('sub_mchid', $payload);
        $this->assertArrayHasKey('appid', $payload);
        $this->assertArrayHasKey('type', $payload);
        $this->assertArrayHasKey('account', $payload);

        // Empty sub_appid should be excluded
        $this->assertArrayNotHasKey('sub_appid', $payload);
    }

    public function testDeleteMerchantReceiver(): void
    {
        $request = new ProfitShareReceiverDeleteRequest(
            subMchId: '1900000109',
            appid: 'wxd678efh567hg6992',
            type: 'MERCHANT_ID',
            account: '1900000109'
        );

        $payload = $request->toPayload();
        $this->assertSame('MERCHANT_ID', $payload['type']);
        $this->assertSame('1900000109', $payload['account']);
    }

    public function testDeletePersonalWechatReceiver(): void
    {
        $request = new ProfitShareReceiverDeleteRequest(
            subMchId: '1900000109',
            appid: 'wxd678efh567hg6992',
            type: 'PERSONAL_OPENID',
            account: 'oxTWIuGaIt6gTKsQRLau2M0yL16E',
            subAppid: 'wx1234567890abcdef'
        );

        $payload = $request->toPayload();
        $this->assertSame('PERSONAL_OPENID', $payload['type']);
        $this->assertSame('oxTWIuGaIt6gTKsQRLau2M0yL16E', $payload['account']);
        $this->assertArrayHasKey('sub_appid', $payload);
        $this->assertSame('wx1234567890abcdef', $payload['sub_appid']);
    }
}
