<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReturnOrder;
use WechatPayBundle\Entity\Merchant;

/**
 * @internal
 */
#[CoversClass(ProfitShareReturnOrder::class)]
class ProfitShareReturnOrderTest extends AbstractEntityTestCase
{
    private ProfitShareReturnOrder $profitShareReturnOrder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->onSetUp();
    }

    protected function onSetUp(): void
    {
        $this->profitShareReturnOrder = new ProfitShareReturnOrder();
    }

    protected function createEntity(): ProfitShareReturnOrder
    {
        return new ProfitShareReturnOrder();
    }

    public function testEntityInitialization(): void
    {
        $this->assertInstanceOf(ProfitShareReturnOrder::class, $this->profitShareReturnOrder);
        $this->assertNull($this->profitShareReturnOrder->getId());
        $this->assertNull($this->profitShareReturnOrder->getMerchant());
        $this->assertNull($this->profitShareReturnOrder->getOrderId());
        $this->assertNull($this->profitShareReturnOrder->getOutOrderNo());
        $this->assertNull($this->profitShareReturnOrder->getReturnNo());
        $this->assertEquals(0, $this->profitShareReturnOrder->getAmount());
        $this->assertNull($this->profitShareReturnOrder->getDescription());
        $this->assertNull($this->profitShareReturnOrder->getResult());
        $this->assertNull($this->profitShareReturnOrder->getFailReason());
        $this->assertNull($this->profitShareReturnOrder->getWechatCreatedAt());
        $this->assertNull($this->profitShareReturnOrder->getWechatFinishedAt());
        $this->assertNull($this->profitShareReturnOrder->getRequestPayload());
        $this->assertNull($this->profitShareReturnOrder->getResponsePayload());
        $this->assertNull($this->profitShareReturnOrder->getMetadata());
    }

    public function testMerchantGetterSetter(): void
    {
        $merchant = $this->createMock(Merchant::class);

        $this->assertNull($this->profitShareReturnOrder->getMerchant());

        $this->profitShareReturnOrder->setMerchant($merchant);
        $this->assertSame($merchant, $this->profitShareReturnOrder->getMerchant());

        $this->profitShareReturnOrder->setMerchant(null);
        $this->assertNull($this->profitShareReturnOrder->getMerchant());
    }

    #[DataProvider('requiredStringFieldsProvider')]
    public function testRequiredStringFieldsGetterSetter(string $field, string $value): void
    {
        switch ($field) {
            case 'subMchId':
                $this->profitShareReturnOrder->setSubMchId($value);
                $this->assertEquals($value, $this->profitShareReturnOrder->getSubMchId());
                break;
            case 'outReturnNo':
                $this->profitShareReturnOrder->setOutReturnNo($value);
                $this->assertEquals($value, $this->profitShareReturnOrder->getOutReturnNo());
                break;
        }
    }

    /**
     * @return list<array{string, string}>
     */
    public static function requiredStringFieldsProvider(): array
    {
        return [
            ['subMchId', '1234567890123456789'],
            ['subMchId', str_repeat('a', 32)],
            ['outReturnNo', 'RETURN1234567890123456'],
            ['outReturnNo', str_repeat('b', 64)],
        ];
    }

    #[DataProvider('optionalStringFieldsProvider')]
    public function testOptionalStringFieldsGetterSetter(string $field, ?string $value): void
    {
        switch ($field) {
            case 'orderId':
                $this->profitShareReturnOrder->setOrderId($value);
                $this->assertEquals($value, $this->profitShareReturnOrder->getOrderId());
                break;
            case 'outOrderNo':
                $this->profitShareReturnOrder->setOutOrderNo($value);
                $this->assertEquals($value, $this->profitShareReturnOrder->getOutOrderNo());
                break;
            case 'returnNo':
                $this->profitShareReturnOrder->setReturnNo($value);
                $this->assertEquals($value, $this->profitShareReturnOrder->getReturnNo());
                break;
            case 'description':
                $this->profitShareReturnOrder->setDescription($value);
                $this->assertEquals($value, $this->profitShareReturnOrder->getDescription());
                break;
        }
    }

    /**
     * @return list<array{string, string|null}>
     */
    public static function optionalStringFieldsProvider(): array
    {
        return [
            ['orderId', null],
            ['orderId', 'WX_ORDER123456789'],
            ['orderId', str_repeat('c', 64)],
            ['outOrderNo', null],
            ['outOrderNo', 'ORDER1234567890123456'],
            ['outOrderNo', str_repeat('d', 64)],
            ['returnNo', null],
            ['returnNo', 'WX_RETURN123456789'],
            ['returnNo', str_repeat('e', 64)],
            ['description', null],
            ['description', ''],
            ['description', '回退原因：用户退款'],
            ['description', str_repeat('f', 80)],
            ['result', null],
            ['result', ''],
            ['result', 'SUCCESS'],
            ['result', 'FAILED'],
            ['result', 'PROCESSING'],
            ['result', str_repeat('g', 20)],
            ['failReason', null],
            ['failReason', ''],
            ['failReason', '余额不足'],
            ['failReason', '订单不存在'],
            ['failReason', str_repeat('h', 64)],
        ];
    }

    #[DataProvider('amountProvider')]
    public function testAmountGetterSetter(int $amount): void
    {
        $this->profitShareReturnOrder->setAmount($amount);
        $this->assertEquals($amount, $this->profitShareReturnOrder->getAmount());
    }

    /**
     * @return list<array{int}>
     */
    public static function amountProvider(): array
    {
        return [
            [0], // 零金额
            [1], // 最小金额
            [100], // 1元
            [1000], // 10元
            [10000], // 100元
            [100000], // 1000元
        ];
    }

    #[DataProvider('datetimeFieldsProvider')]
    public function testDatetimeFieldsGetterSetter(string $field, ?\DateTimeImmutable $value): void
    {
        switch ($field) {
            case 'wechatCreatedAt':
                $this->profitShareReturnOrder->setWechatCreatedAt($value);
                $this->assertEquals($value, $this->profitShareReturnOrder->getWechatCreatedAt());
                break;
            case 'wechatFinishedAt':
                $this->profitShareReturnOrder->setWechatFinishedAt($value);
                $this->assertEquals($value, $this->profitShareReturnOrder->getWechatFinishedAt());
                break;
        }
    }

    /**
     * @return list<array{string, \DateTimeImmutable|null}>
     */
    public static function datetimeFieldsProvider(): array
    {
        return [
            ['wechatCreatedAt', null],
            ['wechatCreatedAt', new \DateTimeImmutable('2024-01-01 12:00:00')],
            ['wechatFinishedAt', null],
            ['wechatFinishedAt', new \DateTimeImmutable('2024-01-01 12:30:00')],
        ];
    }

    #[DataProvider('payloadProvider')]
    public function testPayloadFieldsGetterSetter(string $field, ?string $value): void
    {
        switch ($field) {
            case 'requestPayload':
                $this->profitShareReturnOrder->setRequestPayload($value);
                $this->assertEquals($value, $this->profitShareReturnOrder->getRequestPayload());
                break;
            case 'responsePayload':
                $this->profitShareReturnOrder->setResponsePayload($value);
                $this->assertEquals($value, $this->profitShareReturnOrder->getResponsePayload());
                break;
        }
    }

    /**
     * @return list<array{string, string|null}>
     */
    public static function payloadProvider(): array
    {
        return [
            ['requestPayload', null],
            ['requestPayload', '{"out_return_no": "RETURN123456", "order_id": "WX_ORDER123"}'],
            ['requestPayload', str_repeat('a', 1000)],
            ['responsePayload', null],
            ['responsePayload', '{"return_no": "WX_RETURN123", "result": "SUCCESS"}'],
            ['responsePayload', str_repeat('b', 1000)],
        ];
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    #[DataProvider('metadataProvider')]
    public function testMetadataGetterSetter(?array $metadata): void
    {
        $this->profitShareReturnOrder->setMetadata($metadata);
        $this->assertEquals($metadata, $this->profitShareReturnOrder->getMetadata());
    }

    /**
     * @return list<array{array<string, mixed>|null}>
     */
    public static function metadataProvider(): array
    {
        return [
            [null],
            [[]],
            [['request_id' => 'req_return_123456']],
            [['original_order_id' => 'WX_ORDER123']],
            [['return_reason' => 'USER_REFUND', 'refund_amount' => 5000]],
            [['retry_info' => ['attempt' => 1, 'max_attempts' => 3]]],
            [['wechat_response' => ['code' => 'SUCCESS', 'message' => '成功']]],
        ];
    }

    public function testTimestampGetters(): void
    {
        // 测试初始状态
        $this->assertNull($this->profitShareReturnOrder->getCreatedAt());
        $this->assertNull($this->profitShareReturnOrder->getUpdatedAt());

        // 测试返回类型
        $this->assertIsNullable($this->profitShareReturnOrder->getCreatedAt(), \DateTimeImmutable::class);
        $this->assertIsNullable($this->profitShareReturnOrder->getUpdatedAt(), \DateTimeImmutable::class);
    }

    public function testToString(): void
    {
        $this->assertEmpty((string) $this->profitShareReturnOrder);

        $this->profitShareReturnOrder->setOutReturnNo('RETURN123456789');
        $this->assertEquals('RETURN123456789', (string) $this->profitShareReturnOrder);
    }

    public function testStringableImplementation(): void
    {
        $this->assertInstanceOf(\Stringable::class, $this->profitShareReturnOrder);
        $this->assertIsString((string) $this->profitShareReturnOrder);
    }

    public function testSuccessfulReturnOrderWorkflow(): void
    {
        // 模拟成功的回退订单工作流程
        $merchant = $this->createMock(Merchant::class);

        $this->profitShareReturnOrder->setMerchant($merchant);
        $this->profitShareReturnOrder->setSubMchId('1234567890123456789');
        $this->profitShareReturnOrder->setOrderId('WX_ORDER123456789');
        $this->profitShareReturnOrder->setOutOrderNo('ORDER1234567890123456');
        $this->profitShareReturnOrder->setOutReturnNo('RETURN1234567890123456');
        $this->profitShareReturnOrder->setAmount(5000); // 50元
        $this->profitShareReturnOrder->setDescription('用户申请退款');

        // 设置请求负载
        $requestData = [
            'out_return_no' => 'RETURN1234567890123456',
            'order_id' => 'WX_ORDER123456789',
            'out_order_no' => 'ORDER1234567890123456',
            'return_mchid' => '1234567890123456789',
            'amount' => 5000,
            'description' => '用户申请退款',
        ];
        $requestPayload = json_encode($requestData);
        $this->assertNotFalse($requestPayload, 'Failed to encode request data');
        $this->profitShareReturnOrder->setRequestPayload($requestPayload);

        // 设置为成功状态
        $this->profitShareReturnOrder->setReturnNo('WX_RETURN123456789');
        $this->profitShareReturnOrder->setResult('SUCCESS');
        $this->profitShareReturnOrder->setWechatCreatedAt(new \DateTimeImmutable('2024-01-01 12:00:00'));
        $this->profitShareReturnOrder->setWechatFinishedAt(new \DateTimeImmutable('2024-01-01 12:05:00'));

        // 设置响应负载
        $responseData = [
            'return_no' => 'WX_RETURN123456789',
            'out_return_no' => 'RETURN1234567890123456',
            'order_id' => 'WX_ORDER123456789',
            'out_order_no' => 'ORDER1234567890123456',
            'return_mchid' => '1234567890123456789',
            'amount' => 5000,
            'result' => 'SUCCESS',
            'success_time' => '2024-01-01T12:05:00+08:00',
        ];
        $responsePayload = json_encode($responseData);
        $this->assertNotFalse($responsePayload, 'Failed to encode response data');
        $this->profitShareReturnOrder->setResponsePayload($responsePayload);

        // 设置元数据
        $this->profitShareReturnOrder->setMetadata([
            'request_id' => 'req_return_123456',
            'response_time' => 5.2,
            'original_transaction_amount' => 10000,
            'return_ratio' => 0.5,
        ]);

        // 验证状态
        $this->assertEquals('RETURN1234567890123456', $this->profitShareReturnOrder->getOutReturnNo());
        $this->assertEquals('WX_RETURN123456789', $this->profitShareReturnOrder->getReturnNo());
        $this->assertEquals('SUCCESS', $this->profitShareReturnOrder->getResult());
        $this->assertNull($this->profitShareReturnOrder->getFailReason());
        $this->assertNotNull($this->profitShareReturnOrder->getWechatCreatedAt());
        $this->assertNotNull($this->profitShareReturnOrder->getWechatFinishedAt());
        $this->assertNotNull($this->profitShareReturnOrder->getMetadata());
    }

    public function testFailedReturnOrderWorkflow(): void
    {
        // 模拟失败的回退订单工作流程
        $merchant = $this->createMock(Merchant::class);

        $this->profitShareReturnOrder->setMerchant($merchant);
        $this->profitShareReturnOrder->setSubMchId('1234567890123456789');
        $this->profitShareReturnOrder->setOrderId('WX_ORDER123456789');
        $this->profitShareReturnOrder->setOutOrderNo('ORDER1234567890123456');
        $this->profitShareReturnOrder->setOutReturnNo('RETURN1234567890123456');
        $this->profitShareReturnOrder->setAmount(8000); // 80元
        $this->profitShareReturnOrder->setDescription('商户余额不足回退');

        // 设置为失败状态
        $this->profitShareReturnOrder->setResult('FAILED');
        $this->profitShareReturnOrder->setFailReason('商户余额不足');
        $this->profitShareReturnOrder->setWechatCreatedAt(new \DateTimeImmutable('2024-01-01 12:00:00'));
        // 注意：失败情况下可能没有完成时间

        // 设置错误响应
        $responseData = [
            'code' => 'PARAM_ERROR',
            'message' => '商户余额不足',
        ];
        $errorResponsePayload = json_encode($responseData);
        $this->assertNotFalse($errorResponsePayload, 'Failed to encode error response data');
        $this->profitShareReturnOrder->setResponsePayload($errorResponsePayload);

        // 设置错误元数据
        $this->profitShareReturnOrder->setMetadata([
            'error_code' => 'PARAM_ERROR',
            'error_message' => '商户余额不足',
            'available_balance' => 5000,
            'requested_amount' => 8000,
        ]);

        // 验证状态
        $this->assertEquals('FAILED', $this->profitShareReturnOrder->getResult());
        $this->assertEquals('商户余额不足', $this->profitShareReturnOrder->getFailReason());
        $this->assertNull($this->profitShareReturnOrder->getReturnNo());
        $this->assertNotNull($this->profitShareReturnOrder->getWechatCreatedAt());
        $this->assertNull($this->profitShareReturnOrder->getWechatFinishedAt());
        $this->assertNotNull($this->profitShareReturnOrder->getMetadata());
    }

    public function testProcessingReturnOrderWorkflow(): void
    {
        // 模拟处理中的回退订单工作流程
        $merchant = $this->createMock(Merchant::class);

        $this->profitShareReturnOrder->setMerchant($merchant);
        $this->profitShareReturnOrder->setSubMchId('1234567890123456789');
        $this->profitShareReturnOrder->setOutReturnNo('RETURN1234567890123456');
        $this->profitShareReturnOrder->setAmount(3000);
        $this->profitShareReturnOrder->setDescription('系统自动回退');

        // 设置为处理中状态
        $this->profitShareReturnOrder->setResult('PROCESSING');
        $this->profitShareReturnOrder->setWechatCreatedAt(new \DateTimeImmutable('2024-01-01 12:00:00'));
        // 处理中没有完成时间

        // 验证状态
        $this->assertEquals('PROCESSING', $this->profitShareReturnOrder->getResult());
        $this->assertNull($this->profitShareReturnOrder->getFailReason());
        $this->assertNull($this->profitShareReturnOrder->getReturnNo());
        $this->assertNotNull($this->profitShareReturnOrder->getWechatCreatedAt());
        $this->assertNull($this->profitShareReturnOrder->getWechatFinishedAt());
    }

    public function testReturnOrderStateTransitions(): void
    {
        $states = ['PROCESSING', 'SUCCESS', 'FAILED'];

        foreach ($states as $state) {
            $this->profitShareReturnOrder->setResult($state);
            $this->assertEquals($state, $this->profitShareReturnOrder->getResult());
        }
    }

    public function testAmountValidation(): void
    {
        // 测试各种金额情况
        $amounts = [0, 1, 100, 1000, 10000, 100000];

        foreach ($amounts as $amount) {
            $this->profitShareReturnOrder->setAmount($amount);
            $this->assertEquals($amount, $this->profitShareReturnOrder->getAmount());
        }
    }

    public function testComplexReturnScenario(): void
    {
        // 模拟复杂的回退场景
        $merchant = $this->createMock(Merchant::class);

        // 场景1：部分回退成功
        $partialReturn = new ProfitShareReturnOrder();
        $partialReturn->setMerchant($merchant);
        $partialReturn->setSubMchId('1234567890123456789');
        $partialReturn->setOrderId('WX_ORDER123456789');
        $partialReturn->setOutOrderNo('ORDER1234567890123456');
        $partialReturn->setOutReturnNo('RETURN_PARTIAL_123456');
        $partialReturn->setAmount(3000); // 原订单10000，回退3000
        $partialReturn->setDescription('用户部分退款');
        $partialReturn->setResult('SUCCESS');
        $partialReturn->setReturnNo('WX_RETURN_PARTIAL_123456');
        $partialReturn->setWechatCreatedAt(new \DateTimeImmutable('2024-01-01 12:00:00'));
        $partialReturn->setWechatFinishedAt(new \DateTimeImmutable('2024-01-01 12:02:00'));
        $partialReturn->setMetadata([
            'original_amount' => 10000,
            'return_amount' => 3000,
            'remaining_amount' => 7000,
            'return_ratio' => 0.3,
        ]);

        // 场景2：全额回退
        $fullReturn = new ProfitShareReturnOrder();
        $fullReturn->setMerchant($merchant);
        $fullReturn->setSubMchId('1234567890123456789');
        $fullReturn->setOrderId('WX_ORDER123456789');
        $fullReturn->setOutOrderNo('ORDER1234567890123456');
        $fullReturn->setOutReturnNo('RETURN_FULL_123456');
        $fullReturn->setAmount(7000); // 剩余全额
        $fullReturn->setDescription('用户全额退款');
        $fullReturn->setResult('SUCCESS');
        $fullReturn->setReturnNo('WX_RETURN_FULL_123456');
        $fullReturn->setWechatCreatedAt(new \DateTimeImmutable('2024-01-01 12:05:00'));
        $fullReturn->setWechatFinishedAt(new \DateTimeImmutable('2024-01-01 12:07:00'));
        $fullReturn->setMetadata([
            'original_amount' => 7000,
            'return_amount' => 7000,
            'remaining_amount' => 0,
            'return_ratio' => 1.0,
            'is_final_return' => true,
        ]);

        // 验证部分回退
        $this->assertEquals(3000, $partialReturn->getAmount());
        $this->assertEquals('SUCCESS', $partialReturn->getResult());
        $partialMetadata = $partialReturn->getMetadata();
        $this->assertIsArray($partialMetadata);
        $this->assertArrayHasKey('return_ratio', $partialMetadata);
        $this->assertEquals(0.3, $partialMetadata['return_ratio']);

        // 验证全额回退
        $this->assertEquals(7000, $fullReturn->getAmount());
        $this->assertEquals('SUCCESS', $fullReturn->getResult());
        $fullMetadata = $fullReturn->getMetadata();
        $this->assertIsArray($fullMetadata);
        $this->assertArrayHasKey('return_ratio', $fullMetadata);
        $this->assertArrayHasKey('is_final_return', $fullMetadata);
        $this->assertEquals(1.0, $fullMetadata['return_ratio']);
        $this->assertTrue($fullMetadata['is_final_return']);
    }

    public function testReturnOrderWithNoOriginalOrder(): void
    {
        // 测试没有原始订单号的回退（比如系统错误导致的回退）
        $merchant = $this->createMock(Merchant::class);

        $this->profitShareReturnOrder->setMerchant($merchant);
        $this->profitShareReturnOrder->setSubMchId('1234567890123456789');
        $this->profitShareReturnOrder->setOutReturnNo('RETURN_ERROR_123456');
        $this->profitShareReturnOrder->setAmount(2000);
        $this->profitShareReturnOrder->setDescription('系统错误回退');
        $this->profitShareReturnOrder->setResult('SUCCESS');
        $this->profitShareReturnOrder->setReturnNo('WX_RETURN_ERROR_123456');
        $this->profitShareReturnOrder->setMetadata([
            'error_type' => 'SYSTEM_ERROR',
            'compensation_reason' => '系统维护期间异常分账',
            'auto_generated' => true,
        ]);

        // 验证状态
        $this->assertNull($this->profitShareReturnOrder->getOrderId());
        $this->assertNull($this->profitShareReturnOrder->getOutOrderNo());
        $this->assertEquals('RETURN_ERROR_123456', $this->profitShareReturnOrder->getOutReturnNo());
        $this->assertEquals('SUCCESS', $this->profitShareReturnOrder->getResult());
        $metadata = $this->profitShareReturnOrder->getMetadata();
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('auto_generated', $metadata);
        $this->assertTrue($metadata['auto_generated']);
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            ['subMchId', '1234567890123456789'],
            ['orderId', 'WX_ORDER123456789'],
            ['outOrderNo', 'ORDER1234567890123456'],
            ['outReturnNo', 'RETURN1234567890123456'],
            ['returnNo', 'WX_RETURN123456789'],
            ['amount', 5000],
            ['description', '用户申请退款'],
            ['result', 'SUCCESS'],
            ['failReason', '商户余额不足'],
            ['wechatCreatedAt', new \DateTimeImmutable('2024-01-01 12:00:00')],
            ['wechatFinishedAt', new \DateTimeImmutable('2024-01-01 12:05:00')],
            ['requestPayload', '{"test": "request"}'],
            ['responsePayload', '{"test": "response"}'],
            ['metadata', ['key' => 'value']],
        ];
    }

    /**
     * @param class-string $expectedType
     */
    private function assertIsNullable(mixed $value, string $expectedType): void
    {
        if (null === $value) {
            $this->assertTrue(true);
        } else {
            $this->assertInstanceOf($expectedType, $value);
        }
    }
}
