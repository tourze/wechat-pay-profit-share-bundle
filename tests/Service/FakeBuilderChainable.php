<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Service;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use WeChatPay\BuilderChainable;
use WeChatPay\ClientDecoratorInterface;

/** @internal */
class FakeBuilderChainable implements BuilderChainable
{
    public string $lastSegment = '';

    /**
     * @var array<string, mixed>
     */
    public array $lastOptions = [];

    /**
     * @var list<ResponseInterface>
     */
    private array $responses = [];

    /**
     * @param list<ResponseInterface> $responses
     */
    public function __construct(array $responses = [])
    {
        $this->responses = $responses;
    }

    public function queueResponse(ResponseInterface $response): void
    {
        $this->responses[] = $response;
    }

    public function getDriver(): ClientDecoratorInterface
    {
        throw new \BadMethodCallException('not implemented');
    }

    public function chain(string $segment): BuilderChainable
    {
        $this->lastSegment = $segment;

        return $this;
    }

    public function get(array $options = []): ResponseInterface
    {
        $this->lastOptions = $options;

        return $this->dequeueResponse();
    }

    private function dequeueResponse(): ResponseInterface
    {
        if ([] === $this->responses) {
            return new Response(200, [], '{}');
        }

        return array_shift($this->responses);
    }

    public function post(array $options = []): ResponseInterface
    {
        $this->lastOptions = $options;

        return $this->dequeueResponse();
    }

    public function put(array $options = []): ResponseInterface
    {
        throw new \BadMethodCallException('not implemented');
    }

    public function patch(array $options = []): ResponseInterface
    {
        throw new \BadMethodCallException('not implemented');
    }

    public function delete(array $options = []): ResponseInterface
    {
        throw new \BadMethodCallException('not implemented');
    }

    public function getAsync(array $options = []): PromiseInterface
    {
        throw new \BadMethodCallException('not implemented');
    }

    public function putAsync(array $options = []): PromiseInterface
    {
        throw new \BadMethodCallException('not implemented');
    }

    public function postAsync(array $options = []): PromiseInterface
    {
        throw new \BadMethodCallException('not implemented');
    }

    public function patchAsync(array $options = []): PromiseInterface
    {
        throw new \BadMethodCallException('not implemented');
    }

    public function deleteAsync(array $options = []): PromiseInterface
    {
        throw new \BadMethodCallException('not implemented');
    }
}
