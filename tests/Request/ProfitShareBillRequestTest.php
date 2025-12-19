<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Request;

use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WechatPayProfitShareBundle\Request\ProfitShareBillRequest;

/**
 * @internal
 */
#[CoversClass(ProfitShareBillRequest::class)]
final class ProfitShareBillRequestTest extends TestCase
{
    public function testCreateRequestWithBillDateOnly(): void
    {
        $billDate = new \DateTime('2023-12-01');
        $request = new ProfitShareBillRequest($billDate);

        $this->assertSame($billDate, $request->getBillDate());
        $this->assertNull($request->getSubMchId());
        $this->assertNull($request->getTarType());

        $query = $request->toQuery();
        $expectedQuery = [
            'bill_date' => '2023-12-01',
        ];
        $this->assertSame($expectedQuery, $query);
    }

    public function testCreateRequestWithSubMchId(): void
    {
        $billDate = new \DateTime('2023-12-01');
        $request = new ProfitShareBillRequest(
            billDate: $billDate,
            subMchId: '1900000109'
        );

        $this->assertSame($billDate, $request->getBillDate());
        $this->assertSame('1900000109', $request->getSubMchId());
        $this->assertNull($request->getTarType());

        $query = $request->toQuery();
        $expectedQuery = [
            'bill_date' => '2023-12-01',
            'sub_mchid' => '1900000109',
        ];
        $this->assertSame($expectedQuery, $query);
    }

    public function testCreateRequestWithTarType(): void
    {
        $billDate = new \DateTime('2023-12-01');
        $request = new ProfitShareBillRequest(
            billDate: $billDate,
            tarType: 'gzip'
        );

        $this->assertSame($billDate, $request->getBillDate());
        $this->assertNull($request->getSubMchId());
        $this->assertSame('gzip', $request->getTarType());

        $query = $request->toQuery();
        $expectedQuery = [
            'bill_date' => '2023-12-01',
            'tar_type' => 'gzip',
        ];
        $this->assertSame($expectedQuery, $query);
    }

    public function testCreateRequestWithAllFields(): void
    {
        $billDate = new \DateTime('2023-12-01');
        $request = new ProfitShareBillRequest(
            billDate: $billDate,
            subMchId: '1900000109',
            tarType: 'gzip'
        );

        $this->assertSame($billDate, $request->getBillDate());
        $this->assertSame('1900000109', $request->getSubMchId());
        $this->assertSame('gzip', $request->getTarType());

        $query = $request->toQuery();
        $expectedQuery = [
            'bill_date' => '2023-12-01',
            'sub_mchid' => '1900000109',
            'tar_type' => 'gzip',
        ];
        $this->assertSame($expectedQuery, $query);
    }

    public function testToQueryExcludesEmptyOptionalFields(): void
    {
        $billDate = new \DateTime('2023-12-01');
        $request = new ProfitShareBillRequest(
            billDate: $billDate,
            subMchId: '',
            tarType: ''
        );

        $query = $request->toQuery();

        // Required field should be present
        $this->assertArrayHasKey('bill_date', $query);
        $this->assertSame('2023-12-01', $query['bill_date']);

        // Empty optional fields should be excluded
        $this->assertArrayNotHasKey('sub_mchid', $query);
        $this->assertArrayNotHasKey('tar_type', $query);
    }

    public function testToQueryWithDifferentDateFormats(): void
    {
        $testCases = [
            ['2023-12-01', '2023-12-01'],
            ['2023-01-15', '2023-01-15'],
            ['2024-02-29', '2024-02-29'], // Leap year
        ];

        foreach ($testCases as [$inputDate, $expectedFormat]) {
            $billDate = new \DateTime($inputDate);
            $request = new ProfitShareBillRequest($billDate);
            $query = $request->toQuery();

            $this->assertSame($expectedFormat, $query['bill_date'], "Failed for date: {$inputDate}");
        }
    }

    public function testToQueryWithDateTimeInterface(): void
    {
        $billDate = new \DateTime('2023-12-01 15:30:45');
        $request = new ProfitShareBillRequest($billDate);

        $query = $request->toQuery();
        $this->assertSame('2023-12-01', $query['bill_date']);
    }

    public function testDifferentTarTypes(): void
    {
        $billDate = new \DateTime('2023-12-01');
        $tarTypes = ['gzip', 'plain', 'compress'];

        foreach ($tarTypes as $tarType) {
            $request = new ProfitShareBillRequest(
                billDate: $billDate,
                tarType: $tarType
            );

            $query = $request->toQuery();
            $this->assertArrayHasKey('tar_type', $query);
            $this->assertSame($tarType, $query['tar_type'], "Failed for tar_type: {$tarType}");
        }
    }
}
