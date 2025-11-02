<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareUnfreezeRequest;

/**
 * @internal
 */
#[CoversClass(ProfitShareUnfreezeRequest::class)]
class ProfitShareUnfreezeRequestTest extends TestCase
{
    public function testCreateRequestWithDefaultValues(): void
    {
        $request = new ProfitShareUnfreezeRequest();

        $this->assertNull($request->getSubMchId());
        $this->assertNull($request->getTransactionId());
        $this->assertNull($request->getOutOrderNo());
        $this->assertSame('解冻剩余未分账资金', $request->getDescription());
        $this->assertTrue($request->isUnfreezeUnsplit());

        $payload = $request->toPayload();
        $expectedPayload = [
            'sub_mchid' => null,
            'transaction_id' => null,
            'out_order_no' => null,
            'description' => '解冻剩余未分账资金',
            'unfreeze_unsplit' => true,
        ];
        $this->assertSame($expectedPayload, $payload);
    }

    public function testCreateRequestWithAllValues(): void
    {
        $request = new ProfitShareUnfreezeRequest(
            subMchId: '1900000109',
            transactionId: '4200000452202312011876781234',
            outOrderNo: 'P20150806125346',
            description: '自定义解冻描述',
            unfreezeUnsplit: true
        );

        $this->assertSame('1900000109', $request->getSubMchId());
        $this->assertSame('4200000452202312011876781234', $request->getTransactionId());
        $this->assertSame('P20150806125346', $request->getOutOrderNo());
        $this->assertSame('自定义解冻描述', $request->getDescription());
        $this->assertTrue($request->isUnfreezeUnsplit());

        $payload = $request->toPayload();
        $expectedPayload = [
            'sub_mchid' => '1900000109',
            'transaction_id' => '4200000452202312011876781234',
            'out_order_no' => 'P20150806125346',
            'description' => '自定义解冻描述',
            'unfreeze_unsplit' => true,
        ];
        $this->assertSame($expectedPayload, $payload);
    }

    public function testSetAndGetSubMchId(): void
    {
        $request = new ProfitShareUnfreezeRequest();
        $request->setSubMchId('1900000109');

        $this->assertSame('1900000109', $request->getSubMchId());

        $payload = $request->toPayload();
        $this->assertSame('1900000109', $payload['sub_mchid']);
    }

    public function testSetAndGetTransactionId(): void
    {
        $request = new ProfitShareUnfreezeRequest();
        $request->setTransactionId('4200000452202312011876781234');

        $this->assertSame('4200000452202312011876781234', $request->getTransactionId());

        $payload = $request->toPayload();
        $this->assertSame('4200000452202312011876781234', $payload['transaction_id']);
    }

    public function testSetAndGetOutOrderNo(): void
    {
        $request = new ProfitShareUnfreezeRequest();
        $request->setOutOrderNo('P20150806125346');

        $this->assertSame('P20150806125346', $request->getOutOrderNo());

        $payload = $request->toPayload();
        $this->assertSame('P20150806125346', $payload['out_order_no']);
    }

    public function testSetAndGetDescription(): void
    {
        $request = new ProfitShareUnfreezeRequest();
        $request->setDescription('自定义解冻描述');

        $this->assertSame('自定义解冻描述', $request->getDescription());

        $payload = $request->toPayload();
        $this->assertSame('自定义解冻描述', $payload['description']);
    }

    public function testSetAndGetUnfreezeUnsplit(): void
    {
        $request = new ProfitShareUnfreezeRequest();

        // Default should be true
        $this->assertTrue($request->isUnfreezeUnsplit());

        $request->setUnfreezeUnsplit(false);
        $this->assertFalse($request->isUnfreezeUnsplit());

        $payload = $request->toPayload();
        $this->assertArrayNotHasKey('unfreeze_unsplit', $payload);

        $request->setUnfreezeUnsplit(true);
        $this->assertTrue($request->isUnfreezeUnsplit());

        $payload = $request->toPayload();
        $this->assertTrue($payload['unfreeze_unsplit'] ?? false);
    }

    public function testToPayloadWithUnfreezeUnsplitFalse(): void
    {
        $request = new ProfitShareUnfreezeRequest(
            subMchId: '1900000109',
            transactionId: '4200000452202312011876781234',
            outOrderNo: 'P20150806125346',
            description: '解冻描述',
            unfreezeUnsplit: false
        );

        $payload = $request->toPayload();

        $expectedPayload = [
            'sub_mchid' => '1900000109',
            'transaction_id' => '4200000452202312011876781234',
            'out_order_no' => 'P20150806125346',
            'description' => '解冻描述',
        ];
        $this->assertSame($expectedPayload, $payload);
        $this->assertArrayNotHasKey('unfreeze_unsplit', $payload);
    }

    public function testCreateRequestWithUnfreezeUnsplitFalse(): void
    {
        $request = new ProfitShareUnfreezeRequest(
            unfreezeUnsplit: false
        );

        $this->assertFalse($request->isUnfreezeUnsplit());

        $payload = $request->toPayload();
        $this->assertArrayNotHasKey('unfreeze_unsplit', $payload);
    }

    public function testSetNullValues(): void
    {
        $request = new ProfitShareUnfreezeRequest(
            subMchId: '1900000109',
            transactionId: '4200000452202312011876781234',
            outOrderNo: 'P20150806125346',
            description: '描述',
            unfreezeUnsplit: true
        );

        // Set all values to null
        $request->setSubMchId(null);
        $request->setTransactionId(null);
        $request->setOutOrderNo(null);
        $request->setDescription(null);
        $request->setUnfreezeUnsplit(false);

        $this->assertNull($request->getSubMchId());
        $this->assertNull($request->getTransactionId());
        $this->assertNull($request->getOutOrderNo());
        $this->assertNull($request->getDescription());
        $this->assertFalse($request->isUnfreezeUnsplit());

        $payload = $request->toPayload();
        $expectedPayload = [
            'sub_mchid' => null,
            'transaction_id' => null,
            'out_order_no' => null,
            'description' => null,
        ];
        $this->assertSame($expectedPayload, $payload);
    }

    public function testOnlyRequiredFields(): void
    {
        $request = new ProfitShareUnfreezeRequest(
            subMchId: '1900000109',
            transactionId: '4200000452202312011876781234'
        );

        $payload = $request->toPayload();

        $this->assertSame('1900000109', $payload['sub_mchid']);
        $this->assertSame('4200000452202312011876781234', $payload['transaction_id']);
        $this->assertNull($payload['out_order_no']);
        $this->assertSame('解冻剩余未分账资金', $payload['description']);
        $this->assertTrue($payload['unfreeze_unsplit'] ?? false);
    }
}
