<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareReturnRequest;

/**
 * @internal
 */
#[CoversClass(ProfitShareReturnRequest::class)]
final class ProfitShareReturnRequestTest extends TestCase
{
    public function testCreateRequestWithOrderId(): void
    {
        $request = new ProfitShareReturnRequest(
            subMchId: '1900000109',
            outReturnNo: 'R20150806125346',
            amount: 100,
            description: '回退说明',
            orderId: '3008450740201411110007820472'
        );

        $this->assertSame('1900000109', $request->getSubMchId());
        $this->assertSame('R20150806125346', $request->getOutReturnNo());
        $this->assertSame(100, $request->getAmount());
        $this->assertSame('回退说明', $request->getDescription());
        $this->assertSame('3008450740201411110007820472', $request->getOrderId());
        $this->assertNull($request->getOutOrderNo());

        $payload = $request->toPayload();
        $expectedPayload = [
            'sub_mchid' => '1900000109',
            'out_return_no' => 'R20150806125346',
            'amount' => 100,
            'description' => '回退说明',
            'order_id' => '3008450740201411110007820472',
        ];
        $this->assertSame($expectedPayload, $payload);
    }

    public function testCreateRequestWithOutOrderNo(): void
    {
        $request = new ProfitShareReturnRequest(
            subMchId: '1900000109',
            outReturnNo: 'R20150806125346',
            amount: 100,
            description: '回退说明',
            outOrderNo: 'P20150806125346'
        );

        $this->assertSame('1900000109', $request->getSubMchId());
        $this->assertSame('R20150806125346', $request->getOutReturnNo());
        $this->assertSame(100, $request->getAmount());
        $this->assertSame('回退说明', $request->getDescription());
        $this->assertNull($request->getOrderId());
        $this->assertSame('P20150806125346', $request->getOutOrderNo());

        $payload = $request->toPayload();
        $expectedPayload = [
            'sub_mchid' => '1900000109',
            'out_return_no' => 'R20150806125346',
            'amount' => 100,
            'description' => '回退说明',
            'out_order_no' => 'P20150806125346',
        ];
        $this->assertSame($expectedPayload, $payload);
    }

    public function testCreateRequestWithBothOrderIdAndOutOrderNo(): void
    {
        $request = new ProfitShareReturnRequest(
            subMchId: '1900000109',
            outReturnNo: 'R20150806125346',
            amount: 100,
            description: '回退说明',
            orderId: '3008450740201411110007820472',
            outOrderNo: 'P20150806125346'
        );

        $payload = $request->toPayload();
        $expectedPayload = [
            'sub_mchid' => '1900000109',
            'out_return_no' => 'R20150806125346',
            'amount' => 100,
            'description' => '回退说明',
            'order_id' => '3008450740201411110007820472',
            'out_order_no' => 'P20150806125346',
        ];
        $this->assertSame($expectedPayload, $payload);
    }

    public function testCreateRequestWithoutOrderIdOrOutOrderNoThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('微信分账单号和商户分账单号不能同时为空');

        new ProfitShareReturnRequest(
            subMchId: '1900000109',
            outReturnNo: 'R20150806125346',
            amount: 100,
            description: '回退说明'
        );
    }

    public function testToPayloadExcludesEmptyOrderId(): void
    {
        $request = new ProfitShareReturnRequest(
            subMchId: '1900000109',
            outReturnNo: 'R20150806125346',
            amount: 100,
            description: '回退说明',
            orderId: '',
            outOrderNo: 'P20150806125346'
        );

        $payload = $request->toPayload();
        $this->assertArrayNotHasKey('order_id', $payload);
        $this->assertArrayHasKey('out_order_no', $payload);
    }

    public function testToPayloadExcludesEmptyOutOrderNo(): void
    {
        $request = new ProfitShareReturnRequest(
            subMchId: '1900000109',
            outReturnNo: 'R20150806125346',
            amount: 100,
            description: '回退说明',
            orderId: '3008450740201411110007820472',
            outOrderNo: ''
        );

        $payload = $request->toPayload();
        $this->assertArrayHasKey('order_id', $payload);
        $this->assertArrayNotHasKey('out_order_no', $payload);
    }
}
