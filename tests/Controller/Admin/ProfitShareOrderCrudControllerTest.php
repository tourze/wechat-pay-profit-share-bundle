<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\WechatPayProfitShareBundle\Controller\Admin\ProfitShareOrderCrudController;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Enum\ProfitShareOrderState;

/**
 * 分账订单 CRUD 控制器测试
 *
 * 测试覆盖：
 * - 实体类型配置
 * - CRUD 配置（标签、搜索字段）
 * - Actions 配置（禁用编辑/删除，保留新建）
 * - 字段配置（Index/Detail/New 页面）
 * - 筛选器配置
 * @internal
 */
#[CoversClass(ProfitShareOrderCrudController::class)]
#[RunTestsInSeparateProcesses]
final class ProfitShareOrderCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(ProfitShareOrderCrudController::class);
    }

    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '特约商户号' => ['特约商户号'];
        yield '微信支付订单号' => ['微信支付订单号'];
        yield '商户分账单号' => ['商户分账单号'];
        yield '微信分账单号' => ['微信分账单号'];
        yield '分账状态' => ['分账状态'];
        yield '解冻剩余资金' => ['解冻剩余资金'];
        yield '创建时间' => ['创建时间'];
    }

    public static function provideNewPageFields(): iterable
    {
        yield 'merchant' => ['merchant'];
        yield 'subMchId' => ['subMchId'];
        yield 'transactionId' => ['transactionId'];
        yield 'outOrderNo' => ['outOrderNo'];
        yield 'unfreezeUnsplit' => ['unfreezeUnsplit'];
        // receivers是CollectionField，会渲染为复杂元素，测试基类只支持input字段检查
    }

    public static function provideEditPageFields(): iterable
    {
        // EDIT操作被禁用，返回虚拟字段以避免Empty data provider错误
        yield 'virtual_field' => ['virtual_field'];
    }

    public function testGetEntityFqcn(): void
    {
        $this->assertSame(
            ProfitShareOrder::class,
            ProfitShareOrderCrudController::getEntityFqcn()
        );
    }

    public function testValidationErrors(): void
    {
        $client = $this->createAuthenticatedClient();

        $crawler = $client->request('GET', $this->generateAdminUrl(Action::NEW));
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="ProfitShareOrder"]')->form();
        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testUnfreezeAction(): void
    {
        $client = $this->createAuthenticatedClient();

        // 创建一个测试订单
        $order = $this->createTestOrder();

        // 请求解冻动作
        $client->request('GET', $this->generateAdminUrl('unfreezeAction', ['entityId' => $order->getId()]));

        $this->assertResponseRedirects();
        $location = $client->getResponse()->headers->get('Location');
        $this->assertIsString($location);
        // 验证重定向到详情页
        $this->assertStringContainsString((string) $order->getId(), $location);
    }

    public function testReturnAction(): void
    {
        $client = $this->createAuthenticatedClient();

        // 创建一个测试订单
        $order = $this->createTestOrder();

        // 请求回退动作
        $client->request('GET', $this->generateAdminUrl('returnAction', ['entityId' => $order->getId()]));

        $this->assertResponseRedirects();
        $location = $client->getResponse()->headers->get('Location');
        $this->assertIsString($location);
        // 验证重定向到详情页
        $this->assertStringContainsString((string) $order->getId(), $location);
    }

    private function createTestOrder(): ProfitShareOrder
    {
        $order = new ProfitShareOrder();
        // 设置必要的订单属性
        $order->setSubMchId('1234567890');
        $order->setTransactionId('TX1234567890');
        $order->setOutOrderNo('ORDER123');
        $order->setOrderId('WXORDER123');
        $order->setState(ProfitShareOrderState::PROCESSING);

        // 持久化订单以获取ID
        $entityManager = self::getEntityManager();
        $entityManager->persist($order);
        $entityManager->flush();

        return $order;
    }
}
