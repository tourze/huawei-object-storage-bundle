<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\Adapter;

use League\Flysystem\Config;
use League\Flysystem\UrlGeneration\PublicUrlGenerator as FlysystemPublicUrlGenerator;

/**
 * 华为OBS公共URL生成器
 *
 * 用于生成OBS对象的公共访问URL
 * 支持两种模式：
 * 1. OBS 原生格式：https://{bucket}.{endpoint}/{path}
 * 2. CDN/自定义域名格式：https://{domain}/{path}
 */
class PublicUrlGenerator implements FlysystemPublicUrlGenerator
{
    private string $baseUrl;

    /**
     * @param string $baseUrl      基础URL，可以是：
     *                             - OBS endpoint (如 obs.cn-north-4.myhuaweicloud.com)
     *                             - CDN 域名 (如 cdn.example.com)
     *                             - 自定义域名 (如 files.example.com)
     * @param string $bucket       桶名称
     * @param string $prefix       路径前缀
     * @param bool   $useOBSFormat 是否使用OBS格式（bucket作为子域名）
     */
    public function __construct(
        string $baseUrl,
        string $bucket,
        private readonly string $prefix = '',
        bool $useOBSFormat = true,
    ) {
        // 移除协议前缀
        $cleanUrl = preg_replace('#^https?://#', '', $baseUrl);

        if ($useOBSFormat) {
            // OBS 格式：bucket 作为子域名
            $this->baseUrl = sprintf('https://%s.%s', $bucket, $cleanUrl);
        } else {
            // CDN 格式：直接使用域名
            $this->baseUrl = sprintf('https://%s', $cleanUrl);
        }
    }

    public function publicUrl(string $path, Config $config): string
    {
        // 构建完整的对象路径
        $objectPath = '' !== $this->prefix ? $this->prefix . '/' . ltrim($path, '/') : $path;

        // 对路径进行URL编码，但保留斜杠
        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $objectPath)));

        return sprintf('%s/%s', $this->baseUrl, $encodedPath);
    }
}
