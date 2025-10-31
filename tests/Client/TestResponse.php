<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\Tests\Client;

use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * 简单的测试用Response类
 *
 * @internal
 */
final class TestResponse implements ResponseInterface
{
    public int $statusCode = 200;

    /** @var array<string, mixed> */
    public array $headers = [];

    public string $content = '';

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(bool $throw = true): array
    {
        return $this->headers;
    }

    public function getContent(bool $throw = true): string
    {
        return $this->content;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(bool $throw = true): array
    {
        return [];
    }

    public function cancel(): void
    {
        // 空实现
    }

    public function getInfo(?string $type = null): mixed
    {
        return null;
    }
}
