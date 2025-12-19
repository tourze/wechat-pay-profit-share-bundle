<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\WechatPayProfitShareBundle\Controller\Admin\ProfitShareBillTaskCrudController;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareBillTask;

/**
 * 分账账单任务 CRUD 控制器测试
 *
 * 测试覆盖：
 * - 实体类型配置
 * - CRUD 配置（标签、搜索字段）
 * - Actions 配置（禁用编辑/删除，保留新建）
 * - 字段配置
 * - 筛选器配置
 * @internal
 */
#[CoversClass(ProfitShareBillTaskCrudController::class)]
#[RunTestsInSeparateProcesses]
final class ProfitShareBillTaskCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(ProfitShareBillTaskCrudController::class);
    }

    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '特约商户号' => ['特约商户号'];
        yield '账单日期' => ['账单日期'];
        yield '状态' => ['状态'];
        yield '创建时间' => ['创建时间'];
    }

    public static function provideNewPageFields(): iterable
    {
        yield 'merchant' => ['merchant'];
        yield 'subMchId' => ['subMchId'];
        yield 'billDate' => ['billDate'];
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
            ProfitShareBillTask::class,
            ProfitShareBillTaskCrudController::getEntityFqcn()
        );
    }

    public function testValidationErrors(): void
    {
        $client = $this->createAuthenticatedClient();

        $crawler = $client->request('GET', $this->generateAdminUrl(Action::NEW));
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="ProfitShareBillTask"]')->form();
        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
    }
}
