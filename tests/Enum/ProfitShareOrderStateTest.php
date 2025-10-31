<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOrderState;

#[CoversClass(ProfitShareOrderState::class)]
class ProfitShareOrderStateTest extends AbstractEnumTestCase
{
}