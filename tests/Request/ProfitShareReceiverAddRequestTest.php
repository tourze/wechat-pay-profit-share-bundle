<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReceiverAddRequest;

/**
 * @internal
 */
#[CoversClass(ProfitShareReceiverAddRequest::class)]
final class ProfitShareReceiverAddRequestTest extends TestCase
{
    public function testCreateRequestWithRequiredFields(): void
    {
        $request = new ProfitShareReceiverAddRequest(
            subMchId: '1900000109',
            appid: 'wxd678efh567hg6992',
            type: 'MERCHANT_ID',
            account: '1900000109',
            relationType: 'SERVICE_PROVIDER'
        );

        $this->assertSame('1900000109', $request->getSubMchId());
        $this->assertSame('wxd678efh567hg6992', $request->getAppid());
        $this->assertSame('MERCHANT_ID', $request->getType());
        $this->assertSame('1900000109', $request->getAccount());
        $this->assertSame('SERVICE_PROVIDER', $request->getRelationType());
        $this->assertNull($request->getName());
        $this->assertNull($request->getSubAppid());
        $this->assertNull($request->getCustomRelation());

        $payload = $request->toPayload();
        $expectedPayload = [
            'sub_mchid' => '1900000109',
            'appid' => 'wxd678efh567hg6992',
            'type' => 'MERCHANT_ID',
            'account' => '1900000109',
            'relation_type' => 'SERVICE_PROVIDER',
        ];
        $this->assertSame($expectedPayload, $payload);
    }

    public function testCreateRequestWithAllFields(): void
    {
        $request = new ProfitShareReceiverAddRequest(
            subMchId: '1900000109',
            appid: 'wxd678efh567hg6992',
            type: 'MERCHANT_ID',
            account: '1900000109',
            relationType: 'SERVICE_PROVIDER',
            name: '商户名称',
            subAppid: 'wx1234567890abcdef',
            customRelation: 'CUSTOM_RELATION'
        );

        $this->assertSame('1900000109', $request->getSubMchId());
        $this->assertSame('wxd678efh567hg6992', $request->getAppid());
        $this->assertSame('MERCHANT_ID', $request->getType());
        $this->assertSame('1900000109', $request->getAccount());
        $this->assertSame('SERVICE_PROVIDER', $request->getRelationType());
        $this->assertSame('商户名称', $request->getName());
        $this->assertSame('wx1234567890abcdef', $request->getSubAppid());
        $this->assertSame('CUSTOM_RELATION', $request->getCustomRelation());

        $payload = $request->toPayload();
        $expectedPayload = [
            'sub_mchid' => '1900000109',
            'appid' => 'wxd678efh567hg6992',
            'type' => 'MERCHANT_ID',
            'account' => '1900000109',
            'relation_type' => 'SERVICE_PROVIDER',
            'sub_appid' => 'wx1234567890abcdef',
            'name' => '商户名称',
            'custom_relation' => 'CUSTOM_RELATION',
        ];
        $this->assertSame($expectedPayload, $payload);
    }

    public function testToPayloadExcludesEmptyOptionalFields(): void
    {
        $request = new ProfitShareReceiverAddRequest(
            subMchId: '1900000109',
            appid: 'wxd678efh567hg6992',
            type: 'MERCHANT_ID',
            account: '1900000109',
            relationType: 'SERVICE_PROVIDER',
            name: '',
            subAppid: '',
            customRelation: ''
        );

        $payload = $request->toPayload();

        // Required fields should be present
        $this->assertArrayHasKey('sub_mchid', $payload);
        $this->assertArrayHasKey('appid', $payload);
        $this->assertArrayHasKey('type', $payload);
        $this->assertArrayHasKey('account', $payload);
        $this->assertArrayHasKey('relation_type', $payload);

        // Empty optional fields should be excluded
        $this->assertArrayNotHasKey('sub_appid', $payload);
        $this->assertArrayNotHasKey('name', $payload);
        $this->assertArrayNotHasKey('custom_relation', $payload);
    }

    public function testToPayloadIncludesNonEmptyOptionalFields(): void
    {
        $request = new ProfitShareReceiverAddRequest(
            subMchId: '1900000109',
            appid: 'wxd678efh567hg6992',
            type: 'PERSONAL_OPENID',
            account: 'oxTWIuGaIt6gTKsQRLau2M0yL16E',
            relationType: 'DISTRIBUTOR',
            name: '张三',
            subAppid: 'wx1234567890abcdef',
            customRelation: '分销商'
        );

        $payload = $request->toPayload();

        // All fields should be present
        $this->assertArrayHasKey('sub_mchid', $payload);
        $this->assertArrayHasKey('appid', $payload);
        $this->assertArrayHasKey('type', $payload);
        $this->assertArrayHasKey('account', $payload);
        $this->assertArrayHasKey('relation_type', $payload);
        $this->assertArrayHasKey('sub_appid', $payload);
        $this->assertArrayHasKey('name', $payload);
        $this->assertArrayHasKey('custom_relation', $payload);

        $this->assertSame('wx1234567890abcdef', $payload['sub_appid']);
        $this->assertSame('张三', $payload['name']);
        $this->assertSame('分销商', $payload['custom_relation']);
    }

    public function testPersonalWechatReceiver(): void
    {
        $request = new ProfitShareReceiverAddRequest(
            subMchId: '1900000109',
            appid: 'wxd678efh567hg6992',
            type: 'PERSONAL_OPENID',
            account: 'oxTWIuGaIt6gTKsQRLau2M0yL16E',
            relationType: 'DISTRIBUTOR',
            name: '张三'
        );

        $this->assertSame('PERSONAL_OPENID', $request->getType());
        $this->assertSame('oxTWIuGaIt6gTKsQRLau2M0yL16E', $request->getAccount());
        $this->assertSame('DISTRIBUTOR', $request->getRelationType());

        $payload = $request->toPayload();
        $this->assertSame('PERSONAL_OPENID', $payload['type']);
        $this->assertSame('oxTWIuGaIt6gTKsQRLau2M0yL16E', $payload['account']);
        $this->assertSame('DISTRIBUTOR', $payload['relation_type']);
        $this->assertArrayHasKey('name', $payload);
        $this->assertSame('张三', $payload['name']);
    }
}
