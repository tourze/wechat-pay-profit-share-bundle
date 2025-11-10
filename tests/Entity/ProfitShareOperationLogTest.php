<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOperationLog;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOperationType;
use WechatPayBundle\Entity\Merchant;

/**
 * @internal
 */
#[CoversClass(ProfitShareOperationLog::class)]
class ProfitShareOperationLogTest extends AbstractEntityTestCase
{
    private ProfitShareOperationLog $profitShareOperationLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->profitShareOperationLog = new ProfitShareOperationLog();
    }

    protected function createEntity(): ProfitShareOperationLog
    {
        return new ProfitShareOperationLog();
    }

    public function testEntityInitialization(): void
    {
        $this->assertInstanceOf(ProfitShareOperationLog::class, $this->profitShareOperationLog);
        $this->assertNull($this->profitShareOperationLog->getId());
        $this->assertNull($this->profitShareOperationLog->getMerchant());
        $this->assertNull($this->profitShareOperationLog->getSubMchId());
        $this->assertTrue($this->profitShareOperationLog->isSuccess());
        $this->assertNull($this->profitShareOperationLog->getErrorCode());
        $this->assertNull($this->profitShareOperationLog->getErrorMessage());
        $this->assertNull($this->profitShareOperationLog->getRequestPayload());
        $this->assertNull($this->profitShareOperationLog->getResponsePayload());
        $this->assertNull($this->profitShareOperationLog->getMetadata());
    }

    public function testMerchantGetterSetter(): void
    {
        $merchant = $this->createMock(Merchant::class);

        $this->assertNull($this->profitShareOperationLog->getMerchant());

        $this->profitShareOperationLog->setMerchant($merchant);
        $this->assertSame($merchant, $this->profitShareOperationLog->getMerchant());

        $this->profitShareOperationLog->setMerchant(null);
        $this->assertNull($this->profitShareOperationLog->getMerchant());
    }

    #[DataProvider('subMchIdProvider')]
    public function testSubMchIdGetterSetter(?string $subMchId): void
    {
        $this->profitShareOperationLog->setSubMchId($subMchId);
        $this->assertEquals($subMchId, $this->profitShareOperationLog->getSubMchId());
    }

    /**
     * @return array<array{0:?string}>
     */
    public static function subMchIdProvider(): array
    {
        return [
            [null],
            [''],
            ['1234567890'],
            ['test_merchant_id_123'],
            [str_repeat('a', 32)], // max length
        ];
    }

    public function testTypeGetterSetter(): void
    {
        foreach (ProfitShareOperationType::cases() as $type) {
            $this->profitShareOperationLog->setType($type);
            $this->assertSame($type, $this->profitShareOperationLog->getType());
        }
    }

    #[DataProvider('successProvider')]
    public function testSuccessGetterSetter(bool $success): void
    {
        $this->profitShareOperationLog->setSuccess($success);
        $this->assertEquals($success, $this->profitShareOperationLog->isSuccess());
        $this->assertEquals($success, $this->profitShareOperationLog->isSuccess());
    }

    /**
     * @return array<array{0:bool}>
     */
    public static function successProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    #[DataProvider('errorFieldsProvider')]
    public function testErrorFieldsGetterSetter(string $field, ?string $value): void
    {
        switch ($field) {
            case 'errorCode':
                $this->profitShareOperationLog->setErrorCode($value);
                $this->assertEquals($value, $this->profitShareOperationLog->getErrorCode());
                break;
            case 'errorMessage':
                $this->profitShareOperationLog->setErrorMessage($value);
                $this->assertEquals($value, $this->profitShareOperationLog->getErrorMessage());
                break;
        }
    }

    /**
     * @return array<array{0:string,1:?string}>
     */
    public static function errorFieldsProvider(): array
    {
        return [
            ['errorCode', null],
            ['errorCode', ''],
            ['errorCode', 'INVALID_PARAMETER'],
            ['errorCode', 'SYSTEM_ERROR'],
            ['errorCode', str_repeat('a', 32)],
            ['errorMessage', null],
            ['errorMessage', ''],
            ['errorMessage', '参数错误'],
            ['errorMessage', '系统繁忙，请稍后再试'],
            ['errorMessage', str_repeat('a', 255)],
        ];
    }

    #[DataProvider('payloadProvider')]
    public function testPayloadFieldsGetterSetter(string $field, ?string $value): void
    {
        switch ($field) {
            case 'requestPayload':
                $this->profitShareOperationLog->setRequestPayload($value);
                $this->assertEquals($value, $this->profitShareOperationLog->getRequestPayload());
                break;
            case 'responsePayload':
                $this->profitShareOperationLog->setResponsePayload($value);
                $this->assertEquals($value, $this->profitShareOperationLog->getResponsePayload());
                break;
        }
    }

    /**
     * @return array<array{0:string,1:?string}>
     */
    public static function payloadProvider(): array
    {
        return [
            ['requestPayload', null],
            ['requestPayload', '{"mchid": "1234567890", "out_order_no": "123456"}'],
            ['requestPayload', str_repeat('a', 1000)],
            ['responsePayload', null],
            ['responsePayload', '{"code": "SUCCESS", "message": "成功"}'],
            ['responsePayload', str_repeat('b', 1000)],
        ];
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    #[DataProvider('metadataProvider')]
    public function testMetadataGetterSetter(?array $metadata): void
    {
        $this->profitShareOperationLog->setMetadata($metadata);
        $this->assertEquals($metadata, $this->profitShareOperationLog->getMetadata());
    }

    /**
     * @return array<array{0:?array<string,mixed>}>
     */
    public static function metadataProvider(): array
    {
        return [
            [null],
            [[]],
            [['request_id' => 'req_123456']],
            [['response_time' => 1.234, 'status_code' => 200]],
            [['retry_count' => 3, 'last_error' => 'timeout']],
            [['nested' => ['data' => ['test' => 'value']]]],
        ];
    }

    public function testTimestampGetters(): void
    {
        // 测试初始状态
        $this->assertNull($this->profitShareOperationLog->getCreatedAt());
        $this->assertNull($this->profitShareOperationLog->getUpdatedAt());

        // 测试返回类型
        $this->assertIsNullable($this->profitShareOperationLog->getCreatedAt(), \DateTimeImmutable::class);
        $this->assertIsNullable($this->profitShareOperationLog->getUpdatedAt(), \DateTimeImmutable::class);
    }

    public function testToString(): void
    {
        // 测试成功情况
        $this->profitShareOperationLog->setType(ProfitShareOperationType::REQUEST_ORDER);
        $expected = 'ProfitShareOperationLog(request_order-success-ok)';
        $this->assertEquals($expected, (string) $this->profitShareOperationLog);

        // 测试失败情况
        $this->profitShareOperationLog->setSuccess(false);
        $this->profitShareOperationLog->setErrorCode('SYSTEM_ERROR');
        $expected = 'ProfitShareOperationLog(request_order-failed-SYSTEM_ERROR)';
        $this->assertEquals($expected, (string) $this->profitShareOperationLog);
    }

    public function testStringableImplementation(): void
    {
        $this->assertInstanceOf(\Stringable::class, $this->profitShareOperationLog);
        $this->assertIsString((string) $this->profitShareOperationLog);
    }

    public function testSuccessfulOperationLog(): void
    {
        // 模拟成功的操作日志
        $merchant = $this->createMock(Merchant::class);

        $this->profitShareOperationLog->setMerchant($merchant);
        $this->profitShareOperationLog->setSubMchId('1234567890123456789');
        $this->profitShareOperationLog->setType(ProfitShareOperationType::REQUEST_ORDER);
        $this->profitShareOperationLog->setSuccess(true);
        $this->profitShareOperationLog->setRequestPayload('{"out_order_no": "ORDER123456", "transaction_id": "TX123456789"}');
        $this->profitShareOperationLog->setResponsePayload('{"order_id": "WX_ORDER123", "state": "PROCESSING"}');
        $this->profitShareOperationLog->setMetadata(['response_time' => 0.5, 'retry_count' => 0]);

        $this->assertTrue($this->profitShareOperationLog->isSuccess());
        $this->assertNull($this->profitShareOperationLog->getErrorCode());
        $this->assertNull($this->profitShareOperationLog->getErrorMessage());
        $this->assertNotNull($this->profitShareOperationLog->getRequestPayload());
        $this->assertNotNull($this->profitShareOperationLog->getResponsePayload());
        $this->assertNotNull($this->profitShareOperationLog->getMetadata());
    }

    public function testFailedOperationLog(): void
    {
        // 模拟失败的操作日志
        $merchant = $this->createMock(Merchant::class);

        $this->profitShareOperationLog->setMerchant($merchant);
        $this->profitShareOperationLog->setSubMchId('1234567890123456789');
        $this->profitShareOperationLog->setType(ProfitShareOperationType::ADD_RECEIVER);
        $this->profitShareOperationLog->setSuccess(false);
        $this->profitShareOperationLog->setErrorCode('INVALID_PARAMETER');
        $this->profitShareOperationLog->setErrorMessage('接收方账号不存在');
        $this->profitShareOperationLog->setRequestPayload('{"type": "MERCHANT_ID", "account": "invalid_account"}');
        $this->profitShareOperationLog->setResponsePayload('{"code": "INVALID_PARAMETER", "message": "接收方账号不存在"}');
        $this->profitShareOperationLog->setMetadata(['response_time' => 0.3, 'retry_count' => 1]);

        $this->assertFalse($this->profitShareOperationLog->isSuccess());
        $this->assertEquals('INVALID_PARAMETER', $this->profitShareOperationLog->getErrorCode());
        $this->assertEquals('接收方账号不存在', $this->profitShareOperationLog->getErrorMessage());
        $this->assertNotNull($this->profitShareOperationLog->getRequestPayload());
        $this->assertNotNull($this->profitShareOperationLog->getResponsePayload());
        $this->assertNotNull($this->profitShareOperationLog->getMetadata());
    }

    public function testAllOperationTypes(): void
    {
        $testCases = [
            [ProfitShareOperationType::REQUEST_ORDER, '请求分账'],
            [ProfitShareOperationType::QUERY_ORDER, '查询分账'],
            [ProfitShareOperationType::UNFREEZE_ORDER, '解冻剩余资金'],
            [ProfitShareOperationType::REQUEST_RETURN, '请求分账回退'],
            [ProfitShareOperationType::QUERY_RETURN, '查询分账回退'],
            [ProfitShareOperationType::QUERY_REMAINING_AMOUNT, '查询剩余金额'],
            [ProfitShareOperationType::QUERY_MAX_RATIO, '查询最大分账比例'],
            [ProfitShareOperationType::ADD_RECEIVER, '添加分账接收方'],
            [ProfitShareOperationType::DELETE_RECEIVER, '删除分账接收方'],
            [ProfitShareOperationType::APPLY_BILL, '申请分账账单'],
            [ProfitShareOperationType::DOWNLOAD_BILL, '下载分账账单'],
            [ProfitShareOperationType::NOTIFICATION, '分账通知'],
        ];

        foreach ($testCases as [$type, $description]) {
            $this->profitShareOperationLog->setType($type);
            $this->assertSame($type, $this->profitShareOperationLog->getType());
            $this->assertEquals($description, $type->getLabel());
        }
    }

    public function testComplexOperationWorkflow(): void
    {
        // 模拟一个完整的分账操作工作流程

        // 1. 添加接收方（成功）
        $addReceiverLog = new ProfitShareOperationLog();
        $addReceiverLog->setSubMchId('1234567890123456789');
        $addReceiverLog->setType(ProfitShareOperationType::ADD_RECEIVER);
        $addReceiverLog->setSuccess(true);
        $addReceiverLog->setRequestPayload('{"type": "PERSONAL_OPENID", "account": "ouser123456"}');
        $addReceiverLog->setResponsePayload('{"code": "SUCCESS"}');

        // 2. 请求分账（成功）
        $requestOrderLog = new ProfitShareOperationLog();
        $requestOrderLog->setSubMchId('1234567890123456789');
        $requestOrderLog->setType(ProfitShareOperationType::REQUEST_ORDER);
        $requestOrderLog->setSuccess(true);
        $requestOrderLog->setRequestPayload('{"out_order_no": "ORDER123456"}');
        $requestOrderLog->setResponsePayload('{"order_id": "WX_ORDER123"}');

        // 3. 查询分账（失败）
        $queryOrderLog = new ProfitShareOperationLog();
        $queryOrderLog->setSubMchId('1234567890123456789');
        $queryOrderLog->setType(ProfitShareOperationType::QUERY_ORDER);
        $queryOrderLog->setSuccess(false);
        $queryOrderLog->setErrorCode('SYSTEM_ERROR');
        $queryOrderLog->setErrorMessage('系统繁忙');
        $queryOrderLog->setRequestPayload('{"out_order_no": "ORDER123456"}');
        $queryOrderLog->setResponsePayload('{"code": "SYSTEM_ERROR"}');

        // 验证每个日志的状态
        $this->assertTrue($addReceiverLog->isSuccess());
        $this->assertTrue($requestOrderLog->isSuccess());
        $this->assertFalse($queryOrderLog->isSuccess());
        $this->assertEquals('SYSTEM_ERROR', $queryOrderLog->getErrorCode());

        // 验证类型
        $this->assertEquals(ProfitShareOperationType::ADD_RECEIVER, $addReceiverLog->getType());
        $this->assertEquals(ProfitShareOperationType::REQUEST_ORDER, $requestOrderLog->getType());
        $this->assertEquals(ProfitShareOperationType::QUERY_ORDER, $queryOrderLog->getType());
    }

    /**
     * @return array<int, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            ['subMchId', '1234567890'],
            ['type', ProfitShareOperationType::REQUEST_ORDER],
            ['success', true],
            ['errorCode', 'SYSTEM_ERROR'],
            ['errorMessage', '系统繁忙'],
            ['requestPayload', '{"test": "data"}'],
            ['responsePayload', '{"result": "success"}'],
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
