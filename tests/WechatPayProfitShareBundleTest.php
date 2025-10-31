<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\WechatPayProfitShareBundle\WechatPayProfitShareBundle;

/**
 * @internal
 */
#[CoversClass(WechatPayProfitShareBundle::class)]
#[RunTestsInSeparateProcesses]
final class WechatPayProfitShareBundleTest extends AbstractBundleTestCase
{
    // 基类已提供完整的Bundle测试覆盖，无需额外测试方法
}
