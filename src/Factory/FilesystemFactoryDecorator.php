<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\Factory;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\FileStorageBundle\Factory\FilesystemFactory;
use Tourze\FileStorageBundle\Factory\FilesystemFactoryInterface;
use Tourze\HuaweiObjectStorageBundle\Adapter\HuaweiObsAdapter;
use Tourze\HuaweiObjectStorageBundle\Adapter\PublicUrlGenerator;
use Tourze\HuaweiObjectStorageBundle\Client\ObsClient;

/**
 * FilesystemFactory 装饰器，用于支持华为 OBS
 *
 * 自动检测 OBS 配置，如果配置完整则使用 OBS，否则使用本地存储
 * 所有配置通过 $_ENV 在运行时读取，不在依赖注入容器中配置
 */
#[AsDecorator(decorates: FilesystemFactory::class)]
#[WithMonologChannel(channel: 'huawei_object_storage')]
readonly class FilesystemFactoryDecorator implements FilesystemFactoryInterface
{
    public function __construct(
        #[AutowireDecorated] private FilesystemFactoryInterface $innerFactory,
        private LoggerInterface $logger,
        private HttpClientInterface $httpClient,
    ) {
    }

    public function createFilesystem(): FilesystemOperator
    {
        // 如果 OBS 配置完整，使用 OBS 存储
        if ($this->isObsConfigured()) {
            $this->logger->debug('Creating Huawei OBS file storage');

            return $this->createObsFilesystem();
        }

        // 否则使用原始的文件系统（本地存储）
        $this->logger->debug('Huawei OBS is not config completed.', [
            'innerFactory' => $this->innerFactory::class,
        ]);

        return $this->innerFactory->createFilesystem();
    }

    /**
     * 创建 OBS 文件系统
     *
     * 所有配置从 $_ENV 运行时读取
     */
    private function createObsFilesystem(): FilesystemOperator
    {
        // 从 $_ENV 读取配置
        $accessKey = $_ENV['HUAWEI_OBS_ACCESS_KEY'] ?? '';
        $secretKey = $_ENV['HUAWEI_OBS_SECRET_KEY'] ?? '';
        $bucket = $_ENV['HUAWEI_OBS_BUCKET'] ?? '';
        $prefix = $_ENV['HUAWEI_OBS_PREFIX'] ?? '';
        $region = $_ENV['HUAWEI_OBS_REGION'] ?? null;
        $endpoint = $_ENV['HUAWEI_OBS_ENDPOINT'] ?? null;

        $client = new ObsClient(
            $this->httpClient,
            $accessKey,
            $secretKey,
            $region,
            $endpoint,
            logger: $this->logger,
        );

        $adapter = new HuaweiObsAdapter(
            $client,
            $bucket,
            $prefix
        );

        // 检查是否配置了公共访问域名（CDN 或自定义域名）
        $publicDomain = $_ENV['HUAWEI_OBS_PUBLIC_DOMAIN'] ?? null;
        $urlGenerator = null;
        if (null !== $publicDomain) {
            // 使用配置的公共访问域名（如 CDN），不使用 OBS 格式
            $urlGenerator = new PublicUrlGenerator($publicDomain, $bucket, $prefix, false);
        } elseif (null !== $endpoint) {
            // 如果没有配置公共域名，但配置了 endpoint，使用 OBS 格式
            $urlGenerator = new PublicUrlGenerator($endpoint, $bucket, $prefix, true);
        }

        return new Filesystem($adapter, publicUrlGenerator: $urlGenerator);
    }

    /**
     * 检查 OBS 配置是否完整
     *
     * 从 $_ENV 读取并验证必需的配置项
     */
    private function isObsConfigured(): bool
    {
        return ($_ENV['HUAWEI_OBS_ACCESS_KEY'] ?? '') !== ''
            && ($_ENV['HUAWEI_OBS_SECRET_KEY'] ?? '') !== ''
            && ($_ENV['HUAWEI_OBS_BUCKET'] ?? '') !== '';
    }
}
