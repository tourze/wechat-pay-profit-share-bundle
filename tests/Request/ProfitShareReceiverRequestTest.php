<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReceiverRequest;

/**
 * @internal
 */
#[CoversClass(ProfitShareReceiverRequest::class)]
final class ProfitShareReceiverRequestTest extends TestCase
{
    public function testCreateRequestWithRequiredFields(): void
    {
        $request = new ProfitShareReceiverRequest(
            type: 'MERCHANT_ID',
            account: '1900000109',
            amount: 100,
            description: '分账给商户'
        );

        $this->assertSame('MERCHANT_ID', $request->getType());
        $this->assertSame('1900000109', $request->getAccount());
        $this->assertSame(100, $request->getAmount());
        $this->assertSame('分账给商户', $request->getDescription());
        $this->assertNull($request->getName());

        $payload = $request->toPayload();
        $expectedPayload = [
            'type' => 'MERCHANT_ID',
            'account' => '1900000109',
            'amount' => 100,
            'description' => '分账给商户',
        ];
        $this->assertSame($expectedPayload, $payload);
    }

    public function testCreateRequestWithName(): void
    {
        $request = new ProfitShareReceiverRequest(
            type: 'PERSONAL_OPENID',
            account: 'oxTWIuGaIt6gTKsQRLau2M0yL16E',
            amount: 50,
            description: '分账给个人',
            name: '张三'
        );

        $this->assertSame('PERSONAL_OPENID', $request->getType());
        $this->assertSame('oxTWIuGaIt6gTKsQRLau2M0yL16E', $request->getAccount());
        $this->assertSame(50, $request->getAmount());
        $this->assertSame('分账给个人', $request->getDescription());
        $this->assertSame('张三', $request->getName());

        $payload = $request->toPayload();
        $expectedPayload = [
            'type' => 'PERSONAL_OPENID',
            'account' => 'oxTWIuGaIt6gTKsQRLau2M0yL16E',
            'amount' => 50,
            'description' => '分账给个人',
            'name' => '张三',
        ];
        $this->assertSame($expectedPayload, $payload);
    }

    public function testToPayloadExcludesEmptyName(): void
    {
        $request = new ProfitShareReceiverRequest(
            type: 'MERCHANT_ID',
            account: '1900000109',
            amount: 100,
            description: '分账给商户',
            name: ''
        );

        $payload = $request->toPayload();

        // Required fields should be present
        $this->assertArrayHasKey('type', $payload);
        $this->assertArrayHasKey('account', $payload);
        $this->assertArrayHasKey('amount', $payload);
        $this->assertArrayHasKey('description', $payload);

        // Empty name should be excluded
        $this->assertArrayNotHasKey('name', $payload);
    }

    public function testMerchantReceiver(): void
    {
        $request = new ProfitShareReceiverRequest(
            type: 'MERCHANT_ID',
            account: '1900000109',
            amount: 200,
            description: '分账给服务商商户'
        );

        $payload = $request->toPayload();
        $this->assertSame('MERCHANT_ID', $payload['type']);
        $this->assertSame('1900000109', $payload['account']);
        $this->assertSame(200, $payload['amount']);
        $this->assertSame('分账给服务商商户', $payload['description']);
    }

    public function testPersonalWechatReceiver(): void
    {
        $request = new ProfitShareReceiverRequest(
            type: 'PERSONAL_OPENID',
            account: 'oxTWIuGaIt6gTKsQRLau2M0yL16E',
            amount: 88,
            description: '分账给个人用户',
            name: '李四'
        );

        $payload = $request->toPayload();
        $this->assertSame('PERSONAL_OPENID', $payload['type']);
        $this->assertSame('oxTWIuGaIt6gTKsQRLau2M0yL16E', $payload['account']);
        $this->assertSame(88, $payload['amount']);
        $this->assertSame('分账给个人用户', $payload['description']);
        $this->assertArrayHasKey('name', $payload);
        $this->assertSame('李四', $payload['name']);
    }

    public function testZeroAmount(): void
    {
        $request = new ProfitShareReceiverRequest(
            type: 'MERCHANT_ID',
            account: '1900000109',
            amount: 0,
            description: '零金额分账测试'
        );

        $payload = $request->toPayload();
        $this->assertSame(0, $payload['amount']);
        $this->assertSame('零金额分账测试', $payload['description']);
    }

    public function testLargeAmount(): void
    {
        $request = new ProfitShareReceiverRequest(
            type: 'MERCHANT_ID',
            account: '1900000109',
            amount: 99999999,
            description: '大额分账测试'
        );

        $payload = $request->toPayload();
        $this->assertSame(99999999, $payload['amount']);
    }

    public function testDifferentReceiverTypes(): void
    {
        $receiverTypes = [
            'MERCHANT_ID',
            'PERSONAL_OPENID',
            'PERSONAL_SUB_OPENID',
        ];

        foreach ($receiverTypes as $type) {
            $request = new ProfitShareReceiverRequest(
                type: $type,
                account: 'test_account',
                amount: 100,
                description: '测试'
            );

            $payload = $request->toPayload();
            $this->assertSame($type, $payload['type'], "Failed for type: {$type}");
        }
    }
}
