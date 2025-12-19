<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOrderState;

/**
 * @internal
 */
#[CoversClass(ProfitShareOrderState::class)]
final class ProfitShareOrderStateTest extends AbstractEnumTestCase
{
    public function testToArray(): void
    {
        foreach (ProfitShareOrderState::cases() as $case) {
            $array = $case->toArray();
            $this->assertIsArray($array);
            $this->assertArrayHasKey('value', $array);
            $this->assertArrayHasKey('label', $array);
            $this->assertSame($case->value, $array['value']);
            $this->assertSame($case->getLabel(), $array['label']);
        }
    }
}
