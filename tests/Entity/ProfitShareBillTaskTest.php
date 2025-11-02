<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareBillTask;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareBillStatus;
use WechatPayBundle\Entity\Merchant;

/**
 * @internal
 */
#[CoversClass(ProfitShareBillTask::class)]
class ProfitShareBillTaskTest extends AbstractEntityTestCase
{
    private ProfitShareBillTask $profitShareBillTask;

    protected function onSetUp(): void
    {
        $this->profitShareBillTask = new ProfitShareBillTask();
    }

    protected function createEntity(): ProfitShareBillTask
    {
        return new ProfitShareBillTask();
    }

    public function testEntityInitialization(): void
    {
        $this->assertInstanceOf(ProfitShareBillTask::class, $this->profitShareBillTask);
        $this->assertNull($this->profitShareBillTask->getId());
        $this->assertNull($this->profitShareBillTask->getMerchant());
        $this->assertNull($this->profitShareBillTask->getSubMchId());
        $this->assertEquals(ProfitShareBillStatus::PENDING, $this->profitShareBillTask->getStatus());
        $this->assertNull($this->profitShareBillTask->getDownloadedAt());
        $this->assertNull($this->profitShareBillTask->getLocalPath());
        $this->assertNull($this->profitShareBillTask->getRequestPayload());
        $this->assertNull($this->profitShareBillTask->getResponsePayload());
        $this->assertNull($this->profitShareBillTask->getMetadata());
    }

    public function testMerchantGetterSetter(): void
    {
        $merchant = $this->createMock(Merchant::class);

        $this->assertNull($this->profitShareBillTask->getMerchant());

        $this->profitShareBillTask->setMerchant($merchant);
        $this->assertSame($merchant, $this->profitShareBillTask->getMerchant());

        $this->profitShareBillTask->setMerchant(null);
        $this->assertNull($this->profitShareBillTask->getMerchant());
    }

    #[DataProvider('subMchIdProvider')]
    public function testSubMchIdGetterSetter(?string $subMchId): void
    {
        $this->profitShareBillTask->setSubMchId($subMchId);
        $this->assertEquals($subMchId, $this->profitShareBillTask->getSubMchId());
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

    public function testBillDateGetterSetter(): void
    {
        $billDate = new \DateTimeImmutable('2024-01-01');

        $this->profitShareBillTask->setBillDate($billDate);
        $this->assertSame($billDate, $this->profitShareBillTask->getBillDate());

        // 测试DateTimeInterface兼容性
        $mutableDate = new \DateTime('2024-02-01');
        $immutableDate = \DateTimeImmutable::createFromMutable($mutableDate);
        $this->profitShareBillTask->setBillDate($immutableDate);
        $this->assertEquals($immutableDate, $this->profitShareBillTask->getBillDate());
    }

    #[DataProvider('stringFieldsProvider')]
    public function testStringFieldsGetterSetter(string $field, ?string $value): void
    {
        switch ($field) {
            case 'tarType':
                $this->profitShareBillTask->setTarType($value);
                $this->assertEquals($value, $this->profitShareBillTask->getTarType());
                break;
            case 'hashType':
                $this->profitShareBillTask->setHashType($value);
                $this->assertEquals($value, $this->profitShareBillTask->getHashType());
                break;
            case 'hashValue':
                $this->profitShareBillTask->setHashValue($value);
                $this->assertEquals($value, $this->profitShareBillTask->getHashValue());
                break;
            case 'downloadUrl':
                $this->profitShareBillTask->setDownloadUrl($value);
                $this->assertEquals($value, $this->profitShareBillTask->getDownloadUrl());
                break;
            case 'subMchId':
                $this->profitShareBillTask->setSubMchId($value);
                $this->assertEquals($value, $this->profitShareBillTask->getSubMchId());
                break;
        }
    }

    /**
     * @return array<array{0:string,1:?string}>
     */
    public static function stringFieldsProvider(): array
    {
        return [
            ['tarType', null],
            ['tarType', 'gzip'],
            ['tarType', ''],
            ['tarType', str_repeat('a', 10)],
            ['hashType', null],
            ['hashType', 'md5'],
            ['hashType', 'sha256'],
            ['hashType', str_repeat('a', 10)],
            ['hashValue', null],
            ['hashValue', 'abc123'],
            ['hashValue', str_repeat('a', 1024)],
            ['downloadUrl', null],
            ['downloadUrl', 'https://example.com/download'],
            ['downloadUrl', ''],
            ['downloadUrl', str_repeat('a', 2048)],
            ['localPath', null],
            ['localPath', '/path/to/local/file'],
            ['localPath', str_repeat('a', 255)],
        ];
    }

    public function testStatusGetterSetter(): void
    {
        $this->assertEquals(ProfitShareBillStatus::PENDING, $this->profitShareBillTask->getStatus());

        foreach (ProfitShareBillStatus::cases() as $status) {
            $this->profitShareBillTask->setStatus($status);
            $this->assertSame($status, $this->profitShareBillTask->getStatus());
        }
    }

    public function testDownloadedAtGetterSetter(): void
    {
        $this->assertNull($this->profitShareBillTask->getDownloadedAt());

        $downloadedAt = new \DateTimeImmutable('2024-01-01 12:00:00');
        $this->profitShareBillTask->setDownloadedAt($downloadedAt);
        $this->assertSame($downloadedAt, $this->profitShareBillTask->getDownloadedAt());

        $this->profitShareBillTask->setDownloadedAt(null);
        $this->assertNull($this->profitShareBillTask->getDownloadedAt());
    }

    #[DataProvider('payloadProvider')]
    public function testPayloadFieldsGetterSetter(string $field, ?string $value): void
    {
        switch ($field) {
            case 'requestPayload':
                $this->profitShareBillTask->setRequestPayload($value);
                $this->assertEquals($value, $this->profitShareBillTask->getRequestPayload());
                break;
            case 'responsePayload':
                $this->profitShareBillTask->setResponsePayload($value);
                $this->assertEquals($value, $this->profitShareBillTask->getResponsePayload());
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
            ['requestPayload', '{"test": "data"}'],
            ['requestPayload', str_repeat('a', 1000)],
            ['responsePayload', null],
            ['responsePayload', '{"result": "success"}'],
            ['responsePayload', str_repeat('b', 1000)],
        ];
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    #[DataProvider('metadataProvider')]
    public function testMetadataGetterSetter(?array $metadata): void
    {
        $this->profitShareBillTask->setMetadata($metadata);
        $this->assertEquals($metadata, $this->profitShareBillTask->getMetadata());
    }

    /**
     * @return array<array{0:?array<string,mixed>}>
     */
    public static function metadataProvider(): array
    {
        return [
            [null],
            [[]],
            [['key' => 'value']],
            [['nested' => ['data' => ['test' => 'value']]]],
            [['string' => 'test', 'number' => 123, 'bool' => true, 'null' => null]],
        ];
    }

    public function testTimestampGetters(): void
    {
        // 测试初始状态
        $this->assertNull($this->profitShareBillTask->getCreatedAt());
        $this->assertNull($this->profitShareBillTask->getUpdatedAt());

        // 注意：实际的时间戳设置需要通过TimestampableAware trait的方法
        // 这里只测试getter方法的返回值类型
        $this->assertIsNullable($this->profitShareBillTask->getCreatedAt(), \DateTimeImmutable::class);
        $this->assertIsNullable($this->profitShareBillTask->getUpdatedAt(), \DateTimeImmutable::class);
    }

    public function testToString(): void
    {
        // 测试空值情况
        $this->profitShareBillTask->setBillDate(new \DateTimeImmutable('2024-01-01'));
        $expected = 'ProfitShareBillTask(unknown-2024-01-01-pending)';
        $this->assertEquals($expected, (string) $this->profitShareBillTask);

        // 测试完整信息
        $this->profitShareBillTask->setSubMchId('1234567890');
        $this->profitShareBillTask->setStatus(ProfitShareBillStatus::DOWNLOADED);
        $expected = 'ProfitShareBillTask(1234567890-2024-01-01-downloaded)';
        $this->assertEquals($expected, (string) $this->profitShareBillTask);
    }

    public function testStringableImplementation(): void
    {
        $this->assertInstanceOf(\Stringable::class, $this->profitShareBillTask);
        $this->assertIsString((string) $this->profitShareBillTask);
    }

    public function testComplexWorkflow(): void
    {
        // 模拟一个完整的账单任务工作流程
        $merchant = $this->createMock(Merchant::class);
        $billDate = new \DateTimeImmutable('2024-01-01');

        // 初始化
        $this->profitShareBillTask->setMerchant($merchant);
        $this->profitShareBillTask->setSubMchId('1234567890123456789');
        $this->profitShareBillTask->setBillDate($billDate);
        $this->profitShareBillTask->setTarType('gzip');
        $this->profitShareBillTask->setHashType('md5');
        $this->profitShareBillTask->setHashValue('d41d8cd98f00b204e9800998ecf8427e');

        // 申请账单
        $this->assertEquals(ProfitShareBillStatus::PENDING, $this->profitShareBillTask->getStatus());

        // 账单准备就绪
        $this->profitShareBillTask->setStatus(ProfitShareBillStatus::READY);
        $this->profitShareBillTask->setDownloadUrl('https://api.mch.weixin.qq.com/v3/billdownload/file');

        // 下载账单
        $this->profitShareBillTask->setStatus(ProfitShareBillStatus::DOWNLOADED);
        $this->profitShareBillTask->setDownloadedAt(new \DateTimeImmutable());
        $this->profitShareBillTask->setLocalPath('/tmp/bill_20240101.gz');
        $this->profitShareBillTask->setMetadata(['download_time' => 12.5, 'file_size' => 1024000]);

        // 验证最终状态
        $this->assertEquals(ProfitShareBillStatus::DOWNLOADED, $this->profitShareBillTask->getStatus());
        $this->assertNotNull($this->profitShareBillTask->getDownloadedAt());
        $this->assertNotNull($this->profitShareBillTask->getLocalPath());
        $this->assertNotNull($this->profitShareBillTask->getMetadata());
    }

    /**
     * @return array<int, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            ['subMchId', '1234567890'],
            ['billDate', new \DateTimeImmutable('2024-01-01')],
            ['tarType', 'gzip'],
            ['hashType', 'md5'],
            ['hashValue', 'd41d8cd98f00b204e9800998ecf8427e'],
            ['downloadUrl', 'https://example.com/download'],
            ['status', ProfitShareBillStatus::READY],
            ['downloadedAt', new \DateTimeImmutable('2024-01-01 12:00:00')],
            ['localPath', '/tmp/bill.gz'],
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
