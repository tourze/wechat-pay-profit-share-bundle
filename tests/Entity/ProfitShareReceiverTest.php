<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReceiver;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareReceiverResult;

/**
 * @internal
 */
#[CoversClass(ProfitShareReceiver::class)]
class ProfitShareReceiverTest extends AbstractEntityTestCase
{
    private ProfitShareReceiver $profitShareReceiver;

    protected function onSetUp(): void
    {
        $this->profitShareReceiver = new ProfitShareReceiver();
    }

    protected function createEntity(): ProfitShareReceiver
    {
        return new ProfitShareReceiver();
    }

    public function testEntityInitialization(): void
    {
        $this->assertInstanceOf(ProfitShareReceiver::class, $this->profitShareReceiver);
        $this->assertNull($this->profitShareReceiver->getId());
        $this->assertNull($this->profitShareReceiver->getOrder());
        $this->assertEquals(0, $this->profitShareReceiver->getSequence());
        $this->assertNull($this->profitShareReceiver->getName());
        $this->assertEquals(ProfitShareReceiverResult::PENDING, $this->profitShareReceiver->getResult());
        $this->assertNull($this->profitShareReceiver->getFailReason());
        $this->assertNull($this->profitShareReceiver->getWechatCreatedAt());
        $this->assertNull($this->profitShareReceiver->getWechatFinishedAt());
        $this->assertNull($this->profitShareReceiver->getDetailId());
        $this->assertEquals(0, $this->profitShareReceiver->getRetryCount());
        $this->assertNull($this->profitShareReceiver->getNextRetryAt());
        $this->assertFalse($this->profitShareReceiver->isFinallyFailed());
        $this->assertNull($this->profitShareReceiver->getMetadata());
    }

    public function testOrderGetterSetter(): void
    {
        $order = $this->createMock(ProfitShareOrder::class);

        $this->assertNull($this->profitShareReceiver->getOrder());

        $this->profitShareReceiver->setOrder($order);
        $this->assertSame($order, $this->profitShareReceiver->getOrder());

        $this->profitShareReceiver->setOrder(null);
        $this->assertNull($this->profitShareReceiver->getOrder());
    }

    #[DataProvider('sequenceProvider')]
    public function testSequenceGetterSetter(int $sequence): void
    {
        $this->profitShareReceiver->setSequence($sequence);
        $this->assertEquals($sequence, $this->profitShareReceiver->getSequence());
    }

    /**
     * @return list<array{int}>
     */
    public static function sequenceProvider(): array
    {
        return [
            [0],
            [1],
            [5],
            [10],
            [100],
        ];
    }

    #[DataProvider('requiredStringFieldsProvider')]
    public function testRequiredStringFieldsGetterSetter(string $field, string $value): void
    {
        switch ($field) {
            case 'type':
                $this->profitShareReceiver->setType($value);
                $this->assertEquals($value, $this->profitShareReceiver->getType());
                break;
            case 'account':
                $this->profitShareReceiver->setAccount($value);
                $this->assertEquals($value, $this->profitShareReceiver->getAccount());
                break;
            case 'description':
                $this->profitShareReceiver->setDescription($value);
                $this->assertEquals($value, $this->profitShareReceiver->getDescription());
                break;
        }
    }

    /**
     * @return list<array{string, string}>
     */
    public static function requiredStringFieldsProvider(): array
    {
        return [
            ['type', 'MERCHANT_ID'],
            ['type', 'PERSONAL_OPENID'],
            ['type', str_repeat('a', 32)],
            ['account', '1900000109'],
            ['account', 'ouser1234567890'],
            ['account', str_repeat('b', 64)],
            ['description', '分账给商户'],
            ['description', '分账给个人'],
            ['description', str_repeat('c', 80)],
        ];
    }

    #[DataProvider('optionalStringFieldsProvider')]
    public function testOptionalStringFieldsGetterSetter(string $field, ?string $value): void
    {
        switch ($field) {
            case 'name':
                $this->profitShareReceiver->setName($value);
                $this->assertEquals($value, $this->profitShareReceiver->getName());
                break;
            case 'failReason':
                $this->profitShareReceiver->setFailReason($value);
                $this->assertEquals($value, $this->profitShareReceiver->getFailReason());
                break;
            case 'detailId':
                $this->profitShareReceiver->setDetailId($value);
                $this->assertEquals($value, $this->profitShareReceiver->getDetailId());
                break;
        }
    }

    /**
     * @return list<array{string, string|null}>
     */
    public static function optionalStringFieldsProvider(): array
    {
        return [
            ['name', null],
            ['name', ''],
            ['name', '张三'],
            ['name', str_repeat('a', 1024)],
            ['failReason', null],
            ['failReason', ''],
            ['failReason', '余额不足'],
            ['failReason', '账户异常'],
            ['failReason', str_repeat('b', 64)],
            ['detailId', null],
            ['detailId', ''],
            ['detailId', 'WX_DETAIL123456789'],
            ['detailId', str_repeat('c', 64)],
        ];
    }

    #[DataProvider('amountProvider')]
    public function testAmountGetterSetter(int $amount): void
    {
        $this->profitShareReceiver->setAmount($amount);
        $this->assertEquals($amount, $this->profitShareReceiver->getAmount());
    }

    /**
     * @return list<array{int}>
     */
    public static function amountProvider(): array
    {
        return [
            [1], // 最小金额
            [100], // 1元
            [1000], // 10元
            [10000], // 100元
            [100000], // 1000元
        ];
    }

    public function testResultGetterSetter(): void
    {
        $this->assertEquals(ProfitShareReceiverResult::PENDING, $this->profitShareReceiver->getResult());

        foreach (ProfitShareReceiverResult::cases() as $result) {
            $this->profitShareReceiver->setResult($result);
            $this->assertSame($result, $this->profitShareReceiver->getResult());
        }
    }

    #[DataProvider('datetimeFieldsProvider')]
    public function testDatetimeFieldsGetterSetter(string $field, ?\DateTimeImmutable $value): void
    {
        switch ($field) {
            case 'wechatCreatedAt':
                $this->profitShareReceiver->setWechatCreatedAt($value);
                $this->assertEquals($value, $this->profitShareReceiver->getWechatCreatedAt());
                break;
            case 'wechatFinishedAt':
                $this->profitShareReceiver->setWechatFinishedAt($value);
                $this->assertEquals($value, $this->profitShareReceiver->getWechatFinishedAt());
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
            ['nextRetryAt', null],
            ['nextRetryAt', new \DateTimeImmutable('2024-01-01 13:00:00')],
        ];
    }

    #[DataProvider('retryCountProvider')]
    public function testRetryCountGetterSetter(int $retryCount): void
    {
        $this->profitShareReceiver->setRetryCount($retryCount);
        $this->assertEquals($retryCount, $this->profitShareReceiver->getRetryCount());
    }

    /**
     * @return list<array{int}>
     */
    public static function retryCountProvider(): array
    {
        return [
            [0],
            [1],
            [3],
            [5],
            [10],
        ];
    }

    #[DataProvider('booleanFieldsProvider')]
    public function testBooleanFieldsGetterSetter(string $field, bool $value): void
    {
        switch ($field) {
            case 'finallyFailed':
                $this->profitShareReceiver->setFinallyFailed($value);
                $this->assertEquals($value, $this->profitShareReceiver->isFinallyFailed());
                break;
        }
    }

    /**
     * @return list<array{string, bool}>
     */
    public static function booleanFieldsProvider(): array
    {
        return [
            ['finallyFailed', true],
            ['finallyFailed', false],
        ];
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    #[DataProvider('metadataProvider')]
    public function testMetadataGetterSetter(?array $metadata): void
    {
        $this->profitShareReceiver->setMetadata($metadata);
        $this->assertEquals($metadata, $this->profitShareReceiver->getMetadata());
    }

    /**
     * @return list<array{array<string, mixed>|null}>
     */
    public static function metadataProvider(): array
    {
        return [
            [null],
            [[]],
            [['retry_reason' => 'timeout']],
            [['last_attempt' => '2024-01-01 12:00:00']],
            [['max_retries' => 3, 'retry_delay' => 60]],
            [['error_details' => ['code' => 'INSUFFICIENT_BALANCE', 'message' => '余额不足']]],
        ];
    }

    public function testTimestampGetters(): void
    {
        // 测试初始状态
        $this->assertNull($this->profitShareReceiver->getCreatedAt());
        $this->assertNull($this->profitShareReceiver->getUpdatedAt());

        // 测试返回类型
        $this->assertIsNullable($this->profitShareReceiver->getCreatedAt(), \DateTimeImmutable::class);
        $this->assertIsNullable($this->profitShareReceiver->getUpdatedAt(), \DateTimeImmutable::class);
    }

    public function testToString(): void
    {
        $this->assertEmpty((string) $this->profitShareReceiver);

        $this->profitShareReceiver->setAccount('1900000109');
        $this->assertEquals('1900000109', (string) $this->profitShareReceiver);
    }

    public function testStringableImplementation(): void
    {
        $this->assertInstanceOf(\Stringable::class, $this->profitShareReceiver);
        $this->assertIsString((string) $this->profitShareReceiver);
    }

    public function testSuccessfulReceiverWorkflow(): void
    {
        // 模拟成功的接收方工作流程
        $order = $this->createMock(ProfitShareOrder::class);

        $this->profitShareReceiver->setOrder($order);
        $this->profitShareReceiver->setSequence(0);
        $this->profitShareReceiver->setType('MERCHANT_ID');
        $this->profitShareReceiver->setAccount('1900000109');
        $this->profitShareReceiver->setName('测试商户');
        $this->profitShareReceiver->setAmount(5000); // 50元
        $this->profitShareReceiver->setDescription('分账给商户');

        // 设置为成功
        $this->profitShareReceiver->setResult(ProfitShareReceiverResult::SUCCESS);
        $this->profitShareReceiver->setWechatCreatedAt(new \DateTimeImmutable('2024-01-01 12:00:00'));
        $this->profitShareReceiver->setWechatFinishedAt(new \DateTimeImmutable('2024-01-01 12:05:00'));
        $this->profitShareReceiver->setDetailId('WX_DETAIL123456789');
        $this->profitShareReceiver->setMetadata(['processed_amount' => 5000]);

        // 验证状态
        $this->assertEquals(ProfitShareReceiverResult::SUCCESS, $this->profitShareReceiver->getResult());
        $this->assertEquals(0, $this->profitShareReceiver->getRetryCount());
        $this->assertFalse($this->profitShareReceiver->isFinallyFailed());
        $this->assertNotNull($this->profitShareReceiver->getWechatCreatedAt());
        $this->assertNotNull($this->profitShareReceiver->getWechatFinishedAt());
        $this->assertNotNull($this->profitShareReceiver->getDetailId());
    }

    public function testFailedReceiverWorkflow(): void
    {
        // 模拟失败的接收方工作流程
        $order = $this->createMock(ProfitShareOrder::class);

        $this->profitShareReceiver->setOrder($order);
        $this->profitShareReceiver->setSequence(1);
        $this->profitShareReceiver->setType('PERSONAL_OPENID');
        $this->profitShareReceiver->setAccount('ouser1234567890');
        $this->profitShareReceiver->setAmount(3000); // 30元
        $this->profitShareReceiver->setDescription('分账给个人');

        // 设置为失败
        $this->profitShareReceiver->setResult(ProfitShareReceiverResult::FAILED);
        $this->profitShareReceiver->setFailReason('余额不足');
        $this->profitShareReceiver->setRetryCount(3);
        $this->profitShareReceiver->setFinallyFailed(true);
        $this->profitShareReceiver->setMetadata(['last_error_code' => 'INSUFFICIENT_BALANCE']);

        // 验证状态
        $this->assertEquals(ProfitShareReceiverResult::FAILED, $this->profitShareReceiver->getResult());
        $this->assertEquals('余额不足', $this->profitShareReceiver->getFailReason());
        $this->assertEquals(3, $this->profitShareReceiver->getRetryCount());
        $this->assertTrue($this->profitShareReceiver->isFinallyFailed());
        $this->assertNull($this->profitShareReceiver->getDetailId());
    }

    public function testRetryMechanism(): void
    {
        // 模拟重试机制
        $order = $this->createMock(ProfitShareOrder::class);

        $this->profitShareReceiver->setOrder($order);
        $this->profitShareReceiver->setType('PERSONAL_OPENID');
        $this->profitShareReceiver->setAccount('ouser1234567890');
        $this->profitShareReceiver->setAmount(2000);
        $this->profitShareReceiver->setDescription('分账给个人');

        // 初始状态
        $this->assertEquals(ProfitShareReceiverResult::PENDING, $this->profitShareReceiver->getResult());
        $this->assertEquals(0, $this->profitShareReceiver->getRetryCount());
        $this->assertFalse($this->profitShareReceiver->isFinallyFailed());
        $this->assertNull($this->profitShareReceiver->getNextRetryAt());

        // 第一次重试
        $this->profitShareReceiver->setRetryCount(1);
        $this->profitShareReceiver->setNextRetryAt(new \DateTimeImmutable('2024-01-01 12:05:00'));
        $this->profitShareReceiver->setMetadata(['retry_reason' => 'timeout']);

        // 第二次重试
        $this->profitShareReceiver->setRetryCount(2);
        $this->profitShareReceiver->setNextRetryAt(new \DateTimeImmutable('2024-01-01 12:10:00'));
        $this->profitShareReceiver->setMetadata(['retry_reason' => 'timeout', 'previous_attempts' => 1]);

        // 第三次重试后标记为最终失败
        $this->profitShareReceiver->setRetryCount(3);
        $this->profitShareReceiver->setResult(ProfitShareReceiverResult::FAILED);
        $this->profitShareReceiver->setFinallyFailed(true);
        $this->profitShareReceiver->setFailReason('达到最大重试次数');
        $this->profitShareReceiver->setMetadata([
            'retry_reason' => 'timeout',
            'previous_attempts' => 2,
            'max_retries_reached' => true,
        ]);

        // 验证重试状态
        $this->assertEquals(3, $this->profitShareReceiver->getRetryCount());
        $this->assertTrue($this->profitShareReceiver->isFinallyFailed());
        $this->assertEquals(ProfitShareReceiverResult::FAILED, $this->profitShareReceiver->getResult());
        $this->assertEquals('达到最大重试次数', $this->profitShareReceiver->getFailReason());
    }

    public function testReceiverTypes(): void
    {
        $receiverTypes = [
            'MERCHANT_ID' => '商户号',
            'PERSONAL_OPENID' => '个人openid',
            'PERSONAL_SUB_OPENID' => '个人sub_openid',
        ];

        foreach ($receiverTypes as $type => $description) {
            $receiver = new ProfitShareReceiver();
            $receiver->setType($type);
            $this->assertEquals($type, $receiver->getType());
        }
    }

    public function testAllResultStates(): void
    {
        $results = [
            [ProfitShareReceiverResult::PENDING, '待处理'],
            [ProfitShareReceiverResult::SUCCESS, '成功'],
            [ProfitShareReceiverResult::CLOSED, '已关闭'],
            [ProfitShareReceiverResult::FAILED, '失败'],
        ];

        foreach ($results as [$result, $label]) {
            $this->profitShareReceiver->setResult($result);
            $this->assertSame($result, $this->profitShareReceiver->getResult());
            $this->assertEquals($label, $result->getLabel());
        }
    }

    public function testComplexReceiverScenario(): void
    {
        // 模拟一个复杂的接收方场景
        $order = $this->createMock(ProfitShareOrder::class);

        // 创建商户接收方
        $merchantReceiver = new ProfitShareReceiver();
        $merchantReceiver->setOrder($order);
        $merchantReceiver->setSequence(0);
        $merchantReceiver->setType('MERCHANT_ID');
        $merchantReceiver->setAccount('1900000109');
        $merchantReceiver->setName('测试商户有限公司');
        $merchantReceiver->setAmount(5000);
        $merchantReceiver->setDescription('平台服务费');
        $merchantReceiver->setResult(ProfitShareReceiverResult::SUCCESS);
        $merchantReceiver->setDetailId('WX_DETAIL_MERCHANT_001');
        $merchantReceiver->setWechatCreatedAt(new \DateTimeImmutable('2024-01-01 12:00:00'));
        $merchantReceiver->setWechatFinishedAt(new \DateTimeImmutable('2024-01-01 12:01:00'));

        // 创建个人接收方（失败后重试成功）
        $personalReceiver = new ProfitShareReceiver();
        $personalReceiver->setOrder($order);
        $personalReceiver->setSequence(1);
        $personalReceiver->setType('PERSONAL_OPENID');
        $personalReceiver->setAccount('ouser1234567890');
        $personalReceiver->setName('张三');
        $personalReceiver->setAmount(3000);
        $personalReceiver->setDescription('个人分账');
        $personalReceiver->setResult(ProfitShareReceiverResult::SUCCESS);
        $personalReceiver->setRetryCount(2);
        $personalReceiver->setDetailId('WX_DETAIL_PERSONAL_001');
        $personalReceiver->setWechatCreatedAt(new \DateTimeImmutable('2024-01-01 12:00:00'));
        $personalReceiver->setWechatFinishedAt(new \DateTimeImmutable('2024-01-01 12:03:00'));
        $personalReceiver->setMetadata([
            'retry_reason' => 'timeout',
            'previous_attempts' => 1,
            'final_success_time' => '2024-01-01 12:03:00',
        ]);

        // 验证商户接收方
        $this->assertEquals(ProfitShareReceiverResult::SUCCESS, $merchantReceiver->getResult());
        $this->assertEquals(0, $merchantReceiver->getRetryCount());
        $this->assertFalse($merchantReceiver->isFinallyFailed());

        // 验证个人接收方
        $this->assertEquals(ProfitShareReceiverResult::SUCCESS, $personalReceiver->getResult());
        $this->assertEquals(2, $personalReceiver->getRetryCount());
        $this->assertFalse($personalReceiver->isFinallyFailed());
        $this->assertNotNull($personalReceiver->getMetadata());
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
