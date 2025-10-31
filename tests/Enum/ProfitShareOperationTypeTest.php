<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOperationType;

#[CoversClass(ProfitShareOperationType::class)]
class ProfitShareOperationTypeTest extends AbstractEnumTestCase
{
}