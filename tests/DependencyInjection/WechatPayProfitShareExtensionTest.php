<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\WechatPayProfitShareBundle\DependencyInjection\WechatPayProfitShareExtension;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(WechatPayProfitShareExtension::class)]
class WechatPayProfitShareExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    // 基类已提供完整的Extension测试覆盖，无需额外测试方法
}
