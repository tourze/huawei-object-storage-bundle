<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\Factory;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\HuaweiObjectStorageBundle\Adapter\HuaweiObsAdapter;
use Tourze\HuaweiObjectStorageBundle\Client\ObsClient;
use Tourze\HuaweiObjectStorageBundle\Exception\ConfigurationException;

/**
 * 华为OBS适配器工厂类
 */
readonly class ObsAdapterFactory
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * 创建OBS适配器
     *
     * @param string      $accessKey 访问密钥
     * @param string      $secretKey 密钥
     * @param string      $bucket    桶名称
     * @param string      $prefix    路径前缀
     * @param string|null $region    区域
     * @param string|null $endpoint  端点
     */
    public function create(
        string $accessKey,
        string $secretKey,
        string $bucket,
        string $prefix = '',
        ?string $region = null,
        ?string $endpoint = null,
    ): HuaweiObsAdapter {
        $client = new ObsClient(
            $this->httpClient,
            $accessKey,
            $secretKey,
            $region,
            $endpoint
        );

        return new HuaweiObsAdapter($client, $bucket, $prefix);
    }

    /**
     * 从配置数组创建OBS适配器
     *
     * @param array $config 配置数组，必须包含：
     *                      - access_key: 访问密钥
     *                      - secret_key: 密钥
     *                      - bucket: 桶名称
     *                      可选参数：
     *                      - prefix: 路径前缀
     *                      - region: 区域
     *                      - endpoint: 端点
     */
    /**
     * @param array<string, mixed> $config
     */
    public function createFromConfig(array $config): HuaweiObsAdapter
    {
        // 验证必需的配置项
        $requiredKeys = ['access_key', 'secret_key', 'bucket'];
        foreach ($requiredKeys as $key) {
            if (!isset($config[$key])) {
                throw new ConfigurationException("Missing required config key: {$key}");
            }
            if (!is_string($config[$key]) || '' === trim($config[$key])) {
                throw new ConfigurationException("Config key '{$key}' must be a non-empty string");
            }
        }

        return $this->create(
            $config['access_key'],
            $config['secret_key'],
            $config['bucket'],
            $config['prefix'] ?? '',
            $config['region'] ?? null,
            $config['endpoint'] ?? null
        );
    }
}
