<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\Tests\Adapter;

use League\Flysystem\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\HuaweiObjectStorageBundle\Adapter\PublicUrlGenerator;

/**
 * @internal
 */
#[CoversClass(PublicUrlGenerator::class)]
final class PublicUrlGeneratorTest extends TestCase
{
    public function testGenerateUrlWithOBSFormat(): void
    {
        $generator = new PublicUrlGenerator(
            'obs.cn-north-4.myhuaweicloud.com',
            'my-bucket',
            'uploads',
            true
        );

        $url = $generator->publicUrl('2025/07/document.pdf', new Config());

        $this->assertEquals(
            'https://my-bucket.obs.cn-north-4.myhuaweicloud.com/uploads/2025/07/document.pdf',
            $url
        );
    }

    public function testGenerateUrlWithCDNFormat(): void
    {
        $generator = new PublicUrlGenerator(
            'cdn.example.com',
            'my-bucket',
            'uploads',
            false
        );

        $url = $generator->publicUrl('2025/07/document.pdf', new Config());

        $this->assertEquals(
            'https://cdn.example.com/uploads/2025/07/document.pdf',
            $url
        );
    }

    public function testGenerateUrlWithoutPrefix(): void
    {
        $generator = new PublicUrlGenerator(
            'cdn.example.com',
            'my-bucket',
            '',
            false
        );

        $url = $generator->publicUrl('2025/07/document.pdf', new Config());

        $this->assertEquals(
            'https://cdn.example.com/2025/07/document.pdf',
            $url
        );
    }

    public function testGenerateUrlWithSpecialCharacters(): void
    {
        $generator = new PublicUrlGenerator(
            'cdn.example.com',
            'my-bucket',
            'uploads',
            false
        );

        $url = $generator->publicUrl('2025/07/文档 测试.pdf', new Config());

        $this->assertEquals(
            'https://cdn.example.com/uploads/2025/07/%E6%96%87%E6%A1%A3%20%E6%B5%8B%E8%AF%95.pdf',
            $url
        );
    }

    public function testGenerateUrlWithHttpsPrefix(): void
    {
        $generator = new PublicUrlGenerator(
            'https://cdn.example.com',
            'my-bucket',
            'uploads',
            false
        );

        $url = $generator->publicUrl('document.pdf', new Config());

        // 应该自动移除 https:// 前缀，避免重复
        $this->assertEquals(
            'https://cdn.example.com/uploads/document.pdf',
            $url
        );
    }

    public function testPublicUrl(): void
    {
        $generator = new PublicUrlGenerator(
            'obs.cn-north-4.myhuaweicloud.com',
            'test-bucket',
            'files',
            true
        );

        $config = new Config();
        $url = $generator->publicUrl('test/document.pdf', $config);

        $this->assertEquals(
            'https://test-bucket.obs.cn-north-4.myhuaweicloud.com/files/test/document.pdf',
            $url
        );
    }
}
