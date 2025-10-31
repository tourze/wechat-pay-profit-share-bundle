<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Constraint\IsAnything;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
use Tourze\WechatPayProfitShareBundle\Service\ProfitShareApiExecutor;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Service\WechatPayBuilder;

#[CoversClass(ProfitShareApiExecutor::class)]
class ProfitShareApiExecutorTest extends TestCase
{
    private ProfitShareApiExecutor $executor;
    private WechatPayBuilder $wechatPayBuilder;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->wechatPayBuilder = $this->createMock(WechatPayBuilder::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->executor = new ProfitShareApiExecutor($this->wechatPayBuilder, $this->logger);
    }

    public function testExecuteRequestSuccess(): void
    {
        $merchant = $this->createMock(Merchant::class);
        $segment = '/test/segment';
        $payload = ['key' => 'value'];
        $expectedResponse = ['result' => 'success'];

        $httpClient = $this->createMock(\Psr\Http\Client\ClientInterface::class);
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $body = $this->createMock(\Psr\Http\Message\StreamInterface::class);

        $body->method('getContents')->willReturn(json_encode($expectedResponse));
        $response->method('getBody')->willReturn($body);

        $requestBuilder = $this->createMock(\GuzzleHttp\ClientInterface::class);
        $requestBuilder->method('post')->willReturn($response);

        $chainBuilder = $this->createMock(\GuzzleHttp\ClientInterface::class);
        $chainBuilder->method('chain')->with($segment)->willReturnSelf();
        $chainBuilder->method('post')->willReturn($response);

        $this->wechatPayBuilder->method('genBuilder')
            ->with($merchant)
            ->willReturn($chainBuilder);

        $this->logger->expects($this->exactly(2))
            ->method('info');

        $result = $this->executor->executeRequest($merchant, $segment, $payload);

        $this->assertSame($expectedResponse, $result);
    }

    public function testExecuteRequestFailure(): void
    {
        $merchant = $this->createMock(Merchant::class);
        $segment = '/test/segment';
        $payload = ['key' => 'value'];
        $exception = new \RuntimeException('Request failed');

        $this->logger->expects($this->once())
            ->method('info');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                '微信分账请求失败',
                new IsAnything()
            );

        $this->wechatPayBuilder->method('genBuilder')
            ->with($merchant)
            ->willThrowException($exception);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Request failed');

        $this->executor->executeRequest($merchant, $segment, $payload);
    }

    public function testExecuteQuerySuccess(): void
    {
        $merchant = $this->createMock(Merchant::class);
        $segment = '/test/query';
        $query = ['order_id' => '123'];
        $expectedResponse = ['status' => 'completed'];

        $chainBuilder = $this->createMock(\GuzzleHttp\ClientInterface::class);
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $body = $this->createMock(\Psr\Http\Message\StreamInterface::class);

        $body->method('getContents')->willReturn(json_encode($expectedResponse));
        $response->method('getBody')->willReturn($body);

        $chainBuilder->method('chain')->with($segment)->willReturnSelf();
        $chainBuilder->method('get')->willReturn($response);

        $this->wechatPayBuilder->method('genBuilder')
            ->with($merchant)
            ->willReturn($chainBuilder);

        $this->logger->expects($this->exactly(2))
            ->method('info');

        $result = $this->executor->executeQuery($merchant, $segment, $query);

        $this->assertSame($expectedResponse, $result);
    }

    public function testExecuteQueryFailure(): void
    {
        $merchant = $this->createMock(Merchant::class);
        $segment = '/test/query';
        $query = ['order_id' => '123'];
        $exception = new \RuntimeException('Query failed');

        $this->logger->expects($this->once())
            ->method('info');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                '微信分账查询失败',
                new IsAnything()
            );

        $this->wechatPayBuilder->method('genBuilder')
            ->with($merchant)
            ->willThrowException($exception);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Query failed');

        $this->executor->executeQuery($merchant, $segment, $query);
    }
}