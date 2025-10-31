<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareBillStatus;

#[CoversClass(ProfitShareBillStatus::class)]
class ProfitShareBillStatusTest extends AbstractEnumTestCase
{
}