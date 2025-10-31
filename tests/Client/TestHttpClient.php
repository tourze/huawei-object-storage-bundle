<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\Tests\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * 简单的测试用HttpClient类
 *
 * @internal
 */
final class TestHttpClient implements HttpClientInterface
{
    public ?ResponseInterface $nextResponse = null;

    /** @var array<string, mixed> */
    public array $lastRequest = [];

    /**
     * @param array<string, mixed> $options
     *
     * @phpstan-ignore method.childParameterType
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $this->lastRequest = ['method' => $method, 'url' => $url, 'options' => $options];

        return $this->nextResponse ?? new TestResponse();
    }

    /**
     * @param ResponseInterface|iterable<ResponseInterface> $responses
     */
    public function stream($responses, ?float $timeout = null): ResponseStreamInterface
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * @param array<string, mixed> $options
     *
     * @phpstan-ignore method.childParameterType
     */
    public function withOptions(array $options): static
    {
        return $this;
    }
}
