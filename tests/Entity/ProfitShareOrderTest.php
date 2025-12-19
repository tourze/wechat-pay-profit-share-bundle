<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReceiver;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOrderState;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareReceiverResult;
use WechatPayBundle\Entity\Merchant;

/**
 * @internal
 */
#[CoversClass(ProfitShareOrder::class)]
final class ProfitShareOrderTest extends AbstractEntityTestCase
{
    private ProfitShareOrder $profitShareOrder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->profitShareOrder = new ProfitShareOrder();
    }

    protected function createEntity(): ProfitShareOrder
    {
        return new ProfitShareOrder();
    }

    public function testEntityInitialization(): void
    {
        $this->assertInstanceOf(ProfitShareOrder::class, $this->profitShareOrder);
        $this->assertNull($this->profitShareOrder->getId());
        $this->assertNull($this->profitShareOrder->getMerchant());
        $this->assertNull($this->profitShareOrder->getAppId());
        $this->assertNull($this->profitShareOrder->getSubAppId());
        $this->assertNull($this->profitShareOrder->getOrderId());
        $this->assertEquals(ProfitShareOrderState::PROCESSING, $this->profitShareOrder->getState());
        $this->assertFalse($this->profitShareOrder->isUnfreezeUnsplit());
        $this->assertNull($this->profitShareOrder->getRequestPayload());
        $this->assertNull($this->profitShareOrder->getResponsePayload());
        $this->assertNull($this->profitShareOrder->getWechatCreatedAt());
        $this->assertNull($this->profitShareOrder->getWechatFinishedAt());
        $this->assertNull($this->profitShareOrder->getMetadata());
        $this->assertInstanceOf(ArrayCollection::class, $this->profitShareOrder->getReceivers());
        $this->assertCount(0, $this->profitShareOrder->getReceivers());
    }

    public function testConstructor(): void
    {
        $order = new ProfitShareOrder();
        $this->assertInstanceOf(ArrayCollection::class, $order->getReceivers());
        $this->assertEmpty($order->getReceivers());
    }

    public function testMerchantGetterSetter(): void
    {
        // 使用真实的Merchant实体替代Mock
        $merchant = new Merchant();
        $merchant->setMchId('1900000001');
        $merchant->setPemKey('fake-key');
        $merchant->setPemCert('fake-cert');
        $merchant->setCertSerial('ABC');

        $this->assertNull($this->profitShareOrder->getMerchant());

        $this->profitShareOrder->setMerchant($merchant);
        $this->assertSame($merchant, $this->profitShareOrder->getMerchant());

        $this->profitShareOrder->setMerchant(null);
        $this->assertNull($this->profitShareOrder->getMerchant());
    }

    #[DataProvider('requiredStringFieldsProvider')]
    public function testRequiredStringFieldsGetterSetter(string $field, string $value): void
    {
        switch ($field) {
            case 'subMchId':
                $this->profitShareOrder->setSubMchId($value);
                $this->assertEquals($value, $this->profitShareOrder->getSubMchId());
                break;
            case 'transactionId':
                $this->profitShareOrder->setTransactionId($value);
                $this->assertEquals($value, $this->profitShareOrder->getTransactionId());
                break;
            case 'outOrderNo':
                $this->profitShareOrder->setOutOrderNo($value);
                $this->assertEquals($value, $this->profitShareOrder->getOutOrderNo());
                break;
        }
    }

    /**
     * @return array<int, list<string>>
     */
    public static function requiredStringFieldsProvider(): array
    {
        return [
            ['subMchId', '1234567890123456789'],
            ['subMchId', str_repeat('a', 32)],
            ['transactionId', 'wx1234567890123456789'],
            ['transactionId', str_repeat('b', 32)],
            ['outOrderNo', 'ORDER1234567890123456'],
            ['outOrderNo', str_repeat('c', 64)],
        ];
    }

    #[DataProvider('optionalStringFieldsProvider')]
    public function testOptionalStringFieldsGetterSetter(string $field, ?string $value): void
    {
        switch ($field) {
            case 'appId':
                $this->profitShareOrder->setAppId($value);
                $this->assertEquals($value, $this->profitShareOrder->getAppId());
                break;
            case 'subAppId':
                $this->profitShareOrder->setSubAppId($value);
                $this->assertEquals($value, $this->profitShareOrder->getSubAppId());
                break;
            case 'orderId':
                $this->profitShareOrder->setOrderId($value);
                $this->assertEquals($value, $this->profitShareOrder->getOrderId());
                break;
        }
    }

    /**
     * @return array<int, list<string|null>>
     */
    public static function optionalStringFieldsProvider(): array
    {
        return [
            ['appId', null],
            ['appId', 'wx1234567890abcdef'],
            ['appId', str_repeat('a', 32)],
            ['subAppId', null],
            ['subAppId', 'wx1234567890fedcba'],
            ['subAppId', str_repeat('b', 32)],
            ['orderId', null],
            ['orderId', 'WX_ORDER1234567890'],
            ['orderId', str_repeat('c', 64)],
        ];
    }

    public function testStateGetterSetter(): void
    {
        $this->assertEquals(ProfitShareOrderState::PROCESSING, $this->profitShareOrder->getState());

        foreach (ProfitShareOrderState::cases() as $state) {
            $this->profitShareOrder->setState($state);
            $this->assertSame($state, $this->profitShareOrder->getState());
        }
    }

    #[DataProvider('booleanFieldsProvider')]
    public function testBooleanFieldsGetterSetter(string $field, bool $value): void
    {
        switch ($field) {
            case 'unfreezeUnsplit':
                $this->profitShareOrder->setUnfreezeUnsplit($value);
                $this->assertEquals($value, $this->profitShareOrder->isUnfreezeUnsplit());
                break;
        }
    }

    /**
     * @return array<int, list<string|bool>>
     */
    public static function booleanFieldsProvider(): array
    {
        return [
            ['unfreezeUnsplit', true],
            ['unfreezeUnsplit', false],
        ];
    }

    #[DataProvider('payloadProvider')]
    public function testPayloadFieldsGetterSetter(string $field, ?string $value): void
    {
        switch ($field) {
            case 'requestPayload':
                $this->profitShareOrder->setRequestPayload($value);
                $this->assertEquals($value, $this->profitShareOrder->getRequestPayload());
                break;
            case 'responsePayload':
                $this->profitShareOrder->setResponsePayload($value);
                $this->assertEquals($value, $this->profitShareOrder->getResponsePayload());
                break;
        }
    }

    /**
     * @return array<int, list<string|null>>
     */
    public static function payloadProvider(): array
    {
        return [
            ['requestPayload', null],
            ['requestPayload', '{"receivers": [{"type": "MERCHANT_ID", "account": "123456"}]}'],
            ['requestPayload', str_repeat('a', 1000)],
            ['responsePayload', null],
            ['responsePayload', '{"order_id": "WX_ORDER123", "state": "PROCESSING"}'],
            ['responsePayload', str_repeat('b', 1000)],
        ];
    }

    #[DataProvider('datetimeFieldsProvider')]
    public function testDatetimeFieldsGetterSetter(string $field, ?\DateTimeImmutable $value): void
    {
        switch ($field) {
            case 'wechatCreatedAt':
                $this->profitShareOrder->setWechatCreatedAt($value);
                $this->assertEquals($value, $this->profitShareOrder->getWechatCreatedAt());
                break;
            case 'wechatFinishedAt':
                $this->profitShareOrder->setWechatFinishedAt($value);
                $this->assertEquals($value, $this->profitShareOrder->getWechatFinishedAt());
                break;
        }
    }

    /**
     * @return array<int, list<string|\DateTimeImmutable|null>>
     */
    public static function datetimeFieldsProvider(): array
    {
        return [
            ['wechatCreatedAt', null],
            ['wechatCreatedAt', new \DateTimeImmutable('2024-01-01 12:00:00')],
            ['wechatFinishedAt', null],
            ['wechatFinishedAt', new \DateTimeImmutable('2024-01-01 13:00:00')],
        ];
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    #[DataProvider('metadataProvider')]
    public function testMetadataGetterSetter(?array $metadata): void
    {
        $this->profitShareOrder->setMetadata($metadata);
        $this->assertEquals($metadata, $this->profitShareOrder->getMetadata());
    }

    /**
     * @return array<int, list<array<string, array<string, float|int>|int|string>|null>>
     */
    public static function metadataProvider(): array
    {
        return [
            [null],
            [[]],
            [['request_id' => 'req_123456']],
            [['total_amount' => 10000, 'receiver_count' => 2]],
            [['retry_config' => ['max_retries' => 3, 'delay' => 1.0]]],
        ];
    }

    public function testReceiversCollection(): void
    {
        $receivers = $this->profitShareOrder->getReceivers();
        $this->assertInstanceOf(ArrayCollection::class, $receivers);
        $this->assertEmpty($receivers);

        // 使用真实的Receiver实体替代Mock
        $receiver1 = new ProfitShareReceiver();
        $receiver1->setSequence(0);
        $receiver1->setType('MERCHANT_ID');
        $receiver1->setAccount('1900000109');
        $receiver1->setAmount(500);
        $receiver1->setDescription('测试接收方1');

        $receiver2 = new ProfitShareReceiver();
        $receiver2->setSequence(1);
        $receiver2->setType('PERSONAL_OPENID');
        $receiver2->setAccount('ouser1234567890');
        $receiver2->setAmount(300);
        $receiver2->setDescription('测试接收方2');

        // 测试添加接收方
        $this->profitShareOrder->addReceiver($receiver1);
        $this->assertCount(1, $receivers);
        $this->assertTrue($receivers->contains($receiver1));
        $this->assertSame($this->profitShareOrder, $receiver1->getOrder());

        $this->profitShareOrder->addReceiver($receiver2);
        $this->assertCount(2, $receivers);
        $this->assertTrue($receivers->contains($receiver2));
        $this->assertSame($this->profitShareOrder, $receiver2->getOrder());

        // 测试重复添加不会增加数量
        $this->profitShareOrder->addReceiver($receiver1);
        $this->assertCount(2, $receivers);

        // 测试移除接收方
        $this->profitShareOrder->removeReceiver($receiver1);
        $this->assertCount(1, $receivers);
        $this->assertFalse($receivers->contains($receiver1));
        $this->assertTrue($receivers->contains($receiver2));

        // 测试清空接收方
        $this->profitShareOrder->clearReceivers();
        $this->assertCount(0, $receivers);
    }

    public function testReceiversBidirectionalRelationship(): void
    {
        // 使用真实的Receiver实体测试双向关联
        $receiver = new ProfitShareReceiver();
        $receiver->setSequence(0);
        $receiver->setType('MERCHANT_ID');
        $receiver->setAccount('1900000109');
        $receiver->setAmount(500);
        $receiver->setDescription('测试接收方');

        // 初始状态：receiver没有关联order
        $this->assertNull($receiver->getOrder());

        // 添加receiver到order，应该自动设置双向关联
        $this->profitShareOrder->addReceiver($receiver);

        // 验证双向关联已建立
        $this->assertSame($this->profitShareOrder, $receiver->getOrder());
        $this->assertTrue($this->profitShareOrder->getReceivers()->contains($receiver));
    }

    public function testTimestampGetters(): void
    {
        // 测试初始状态
        $this->assertNull($this->profitShareOrder->getCreatedAt());
        $this->assertNull($this->profitShareOrder->getUpdatedAt());

        // 测试返回类型
        $this->assertIsNullable($this->profitShareOrder->getCreatedAt(), \DateTimeImmutable::class);
        $this->assertIsNullable($this->profitShareOrder->getUpdatedAt(), \DateTimeImmutable::class);
    }

    public function testToString(): void
    {
        $this->assertEmpty((string) $this->profitShareOrder);

        $this->profitShareOrder->setOutOrderNo('ORDER123456789');
        $this->assertSame('ORDER123456789', $this->profitShareOrder->__toString());
    }

    public function testStringableImplementation(): void
    {
        $this->assertInstanceOf(\Stringable::class, $this->profitShareOrder);
        $this->assertIsString((string) $this->profitShareOrder);
    }

    public function testComplexOrderWorkflow(): void
    {
        // 模拟一个完整的分账订单工作流程，使用真实Merchant实体
        $merchant = new Merchant();
        $merchant->setMchId('1900000001');
        $merchant->setPemKey('fake-key');
        $merchant->setPemCert('fake-cert');
        $merchant->setCertSerial('ABC');

        // 1. 初始化订单
        $this->profitShareOrder->setMerchant($merchant);
        $this->profitShareOrder->setSubMchId('1234567890123456789');
        $this->profitShareOrder->setAppId('wx1234567890abcdef');
        $this->profitShareOrder->setTransactionId('wx1234567890123456789');
        $this->profitShareOrder->setOutOrderNo('ORDER1234567890123456');
        $this->profitShareOrder->setUnfreezeUnsplit(true);

        // 2. 添加接收方
        $receiver1 = new ProfitShareReceiver();
        $receiver1->setSequence(0);
        $receiver1->setType('MERCHANT_ID');
        $receiver1->setAccount('1900000109');
        $receiver1->setAmount(500);
        $receiver1->setDescription('分账给商户');
        $receiver1->setResult(ProfitShareReceiverResult::SUCCESS);

        $receiver2 = new ProfitShareReceiver();
        $receiver2->setSequence(1);
        $receiver2->setType('PERSONAL_OPENID');
        $receiver2->setAccount('ouser1234567890');
        $receiver2->setAmount(300);
        $receiver2->setDescription('分账给个人');
        $receiver2->setResult(ProfitShareReceiverResult::PENDING);

        $this->profitShareOrder->addReceiver($receiver1);
        $this->profitShareOrder->addReceiver($receiver2);

        // 3. 设置请求负载
        $requestData = [
            'appid' => 'wx1234567890abcdef',
            'transaction_id' => 'wx1234567890123456789',
            'out_order_no' => 'ORDER1234567890123456',
            'unfreeze_unsplit' => true,
            'receivers' => [
                [
                    'type' => 'MERCHANT_ID',
                    'account' => '1900000109',
                    'amount' => 500,
                    'description' => '分账给商户',
                ],
                [
                    'type' => 'PERSONAL_OPENID',
                    'account' => 'ouser1234567890',
                    'amount' => 300,
                    'description' => '分账给个人',
                ],
            ],
        ];
        $this->profitShareOrder->setRequestPayload(json_encode($requestData, JSON_THROW_ON_ERROR));

        // 4. 模拟微信响应
        $responseData = [
            'order_id' => 'WX_ORDER123456789',
            'state' => 'PROCESSING',
        ];
        $this->profitShareOrder->setOrderId('WX_ORDER123456789');
        $this->profitShareOrder->setState(ProfitShareOrderState::PROCESSING);
        $this->profitShareOrder->setResponsePayload(json_encode($responseData, JSON_THROW_ON_ERROR));
        $this->profitShareOrder->setWechatCreatedAt(new \DateTimeImmutable('2024-01-01 12:00:00'));

        // 验证最终状态
        $this->assertEquals('WX_ORDER123456789', $this->profitShareOrder->getOrderId());
        $this->assertEquals(ProfitShareOrderState::PROCESSING, $this->profitShareOrder->getState());
        $this->assertTrue($this->profitShareOrder->isUnfreezeUnsplit());
        $this->assertNotNull($this->profitShareOrder->getRequestPayload());
        $this->assertNotNull($this->profitShareOrder->getResponsePayload());
        $this->assertNotNull($this->profitShareOrder->getWechatCreatedAt());
        $this->assertCount(2, $this->profitShareOrder->getReceivers());
    }

    public function testOrderStateTransitions(): void
    {
        $states = [
            ProfitShareOrderState::PROCESSING,
            ProfitShareOrderState::FINISHED,
            ProfitShareOrderState::CLOSED,
        ];

        foreach ($states as $state) {
            $this->profitShareOrder->setState($state);
            $this->assertSame($state, $this->profitShareOrder->getState());
            $this->assertIsString($state->getLabel());
        }
    }

    public function testOrderCompleteWorkflow(): void
    {
        // 模拟订单完成流程
        $this->profitShareOrder->setSubMchId('1234567890123456789');
        $this->profitShareOrder->setTransactionId('wx1234567890123456789');
        $this->profitShareOrder->setOutOrderNo('ORDER1234567890123456');

        // 处理中
        $this->profitShareOrder->setState(ProfitShareOrderState::PROCESSING);
        $this->profitShareOrder->setWechatCreatedAt(new \DateTimeImmutable('2024-01-01 12:00:00'));

        // 已完成
        $this->profitShareOrder->setState(ProfitShareOrderState::FINISHED);
        $this->profitShareOrder->setOrderId('WX_ORDER123456789');
        $this->profitShareOrder->setWechatFinishedAt(new \DateTimeImmutable('2024-01-01 12:30:00'));

        // 验证状态
        $this->assertEquals(ProfitShareOrderState::FINISHED, $this->profitShareOrder->getState());
        $this->assertNotNull($this->profitShareOrder->getOrderId());
        $this->assertNotNull($this->profitShareOrder->getWechatFinishedAt());
    }

    /**
     * @return array<int, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            ['subMchId', '1234567890123456789'],
            ['appId', 'wx1234567890abcdef'],
            ['subAppId', 'wx1234567890fedcba'],
            ['transactionId', 'wx1234567890123456789'],
            ['outOrderNo', 'ORDER1234567890123456'],
            ['orderId', 'WX_ORDER123456789'],
            ['state', ProfitShareOrderState::PROCESSING],
            ['unfreezeUnsplit', true],
            ['requestPayload', '{"test": "data"}'],
            ['responsePayload', '{"result": "success"}'],
            ['wechatCreatedAt', new \DateTimeImmutable('2024-01-01 12:00:00')],
            ['wechatFinishedAt', new \DateTimeImmutable('2024-01-01 13:00:00')],
            ['metadata', ['key' => 'value']],
            ['finishTime', '2024-01-01T13:00:00+08:00'],
            ['successTime', '2024-01-01T13:00:00+08:00'],
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
