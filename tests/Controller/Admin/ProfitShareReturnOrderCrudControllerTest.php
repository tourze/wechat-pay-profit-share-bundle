<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\WechatPayProfitShareBundle\Controller\Admin\ProfitShareReturnOrderCrudController;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReturnOrder;

/**
 * 分账回退单 CRUD 控制器测试
 *
 * 测试覆盖：
 * - 实体类型配置
 * - CRUD 配置（标签、搜索字段）
 * - Actions 配置（禁用新建/编辑/删除）
 * - 字段配置（包括金额格式化）
 * - 筛选器配置
 * @internal
 */
#[CoversClass(ProfitShareReturnOrderCrudController::class)]
#[RunTestsInSeparateProcesses]
final class ProfitShareReturnOrderCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(ProfitShareReturnOrderCrudController::class);
    }

    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '商户回退单号' => ['商户回退单号'];
        yield '商户分账单号' => ['商户分账单号'];
        yield '特约商户号' => ['特约商户号'];
        yield '回退金额' => ['回退金额'];
        yield '回退结果' => ['回退结果'];
        yield '创建时间' => ['创建时间'];
    }

    /**
     * 重写基类的数据提供者以避免空数据集错误
     * 由于NEW操作被禁用，这些数据不会被实际使用
     *
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        // NEW action is disabled for this entity - provide minimal dummy field
        yield 'disabled' => ['__disabled__'];
    }

    /**
     * 重写基类的数据提供者以避免空数据集错误
     * 由于EDIT操作被禁用，这些数据不会被实际使用
     *
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        // EDIT action is disabled for this entity - provide minimal dummy field
        yield 'disabled' => ['__disabled__'];
    }

    public function testGetEntityFqcn(): void
    {
        $this->assertSame(
            ProfitShareReturnOrder::class,
            ProfitShareReturnOrderCrudController::getEntityFqcn()
        );
    }
}
