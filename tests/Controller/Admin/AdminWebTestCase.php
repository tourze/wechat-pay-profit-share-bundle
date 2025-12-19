<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\WechatPayProfitShareBundle\Controller\Admin\AbstractProfitShareCrudController;

/**
 * Admin 控制器测试基类
 *
 * 提供后台管理测试的公共方法和配置
 */
#[CoversClass(AbstractProfitShareCrudController::class)]
#[RunTestsInSeparateProcesses]
abstract class AdminWebTestCase extends AbstractWebTestCase
{
    /**
     * 断言页面加载成功
     */
    protected function assertPageLoads(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
    }

    /**
     * 断言页面包含指定文本
     */
    protected function assertPageContainsText(string $url, string $text): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($text, $crawler->html());
    }

    /**
     * 断言操作被禁用（返回 403 或重定向）
     */
    protected function assertActionDisabled(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isRedirection() || 403 === $response->getStatusCode(),
            sprintf('Expected redirect or 403, got %d', $response->getStatusCode())
        );
    }

    /**
     * 断言页面包含 Flash 消息
     */
    protected function assertHasFlashMessage(string $type, string $message): void
    {
        $client = static::getClient();
        $session = $client->getRequest()->getSession();

        if ($session) {
            $flashBag = $session->getFlashBag();
            $messages = $flashBag->get($type);

            $this->assertContains(
                $message,
                $messages,
                sprintf('Expected flash message "%s" not found in %s messages', $message, $type)
            );
        }
    }
}
