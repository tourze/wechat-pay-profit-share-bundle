<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\WechatPayProfitShareBundle\Controller\Admin\ProfitShareOperationLogCrudController;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOperationLog;

/**
 * 分账操作日志 CRUD 控制器测试
 *
 * 测试覆盖：
 * - 实体类型配置
 * - CRUD 配置（标签、搜索字段）
 * - Actions 配置（禁用新建/编辑/删除）
 * - 字段配置
 * - 筛选器配置
 * @internal
 */
#[CoversClass(ProfitShareOperationLogCrudController::class)]
#[RunTestsInSeparateProcesses]
final class ProfitShareOperationLogCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(ProfitShareOperationLogCrudController::class);
    }

    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '操作类型' => ['操作类型'];
        yield '特约商户号' => ['特约商户号'];
        yield '是否成功' => ['是否成功'];
        yield '创建时间' => ['创建时间'];
    }

    public function testGetEntityFqcn(): void
    {
        $this->assertSame(
            ProfitShareOperationLog::class,
            ProfitShareOperationLogCrudController::getEntityFqcn()
        );
    }

    /**
     * 提供新建页面的字段配置
     *
     * 注意：该Controller禁用了NEW操作，提供虚拟字段名以满足PHPUnit要求
     */
    public static function provideNewPageFields(): iterable
    {
        // NEW action is disabled for log entities - provide minimal dummy field
        yield 'disabled' => ['__disabled__'];
    }

    /**
     * 提供编辑页面的字段配置
     *
     * 注意：该Controller禁用了EDIT操作，提供虚拟字段名以满足PHPUnit要求
     */
    public static function provideEditPageFields(): iterable
    {
        // EDIT action is disabled for log entities - provide minimal dummy field
        yield 'disabled' => ['__disabled__'];
    }
}
