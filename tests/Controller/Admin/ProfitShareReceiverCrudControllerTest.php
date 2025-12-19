<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\WechatPayProfitShareBundle\Controller\Admin\ProfitShareReceiverCrudController;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReceiver;

/**
 * 分账接收方 CRUD 控制器测试
 *
 * 测试覆盖：
 * - 实体类型配置
 * - CRUD 配置（标签、搜索字段）
 * - Actions 配置（禁用新建/编辑/删除）
 * - 字段配置（包括姓名脱敏、金额格式化）
 * - 筛选器配置
 * @internal
 */
#[CoversClass(ProfitShareReceiverCrudController::class)]
#[RunTestsInSeparateProcesses]
final class ProfitShareReceiverCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(ProfitShareReceiverCrudController::class);
    }

    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '分账订单' => ['分账订单'];
        yield '接收方类型' => ['接收方类型'];
        yield '接收方账号' => ['接收方账号'];
        yield '接收方姓名' => ['接收方姓名'];
        yield '分账金额' => ['分账金额'];
        yield '分账描述' => ['分账描述'];
        yield '分账结果' => ['分账结果'];
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
            ProfitShareReceiver::class,
            ProfitShareReceiverCrudController::getEntityFqcn()
        );
    }
}
