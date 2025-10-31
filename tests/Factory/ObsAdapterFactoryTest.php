<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\Tests\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Tourze\HuaweiObjectStorageBundle\Adapter\HuaweiObsAdapter;
use Tourze\HuaweiObjectStorageBundle\Factory\ObsAdapterFactory;

/**
 * @internal
 */
#[CoversClass(ObsAdapterFactory::class)]
final class ObsAdapterFactoryTest extends TestCase
{
    private HttpClientInterface $mockHttpClient;

    private ObsAdapterFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        // 创建简单的HTTP客户端Mock
        $this->mockHttpClient = $this->createMock(HttpClientInterface::class);

        $this->factory = new ObsAdapterFactory($this->mockHttpClient);
    }

    public function testCreateAdapterWithBasicParameters(): void
    {
        $adapter = $this->factory->create(
            'test-access-key',
            'test-secret-key',
            'test-bucket'
        );

        // 验证工厂创建了正确类型的适配器
        $this->assertInstanceOf(
            HuaweiObsAdapter::class,
            $adapter
        );
    }

    public function testCreateAdapterWithAllParameters(): void
    {
        $adapter = $this->factory->create(
            'test-access-key',
            'test-secret-key',
            'test-bucket',
            'test-prefix',
            'cn-south-1',
            'custom.endpoint.com'
        );

        // 验证全参数工厂也创建正确类型
        $this->assertInstanceOf(
            HuaweiObsAdapter::class,
            $adapter
        );
    }

    public function testCreateFromConfigWithMinimalConfig(): void
    {
        $config = [
            'access_key' => 'test-access-key',
            'secret_key' => 'test-secret-key',
            'bucket' => 'test-bucket',
        ];

        $adapter = $this->factory->createFromConfig($config);

        // 验证最小配置也能创建正确适配器
        $this->assertInstanceOf(
            HuaweiObsAdapter::class,
            $adapter
        );
    }

    public function testCreateFromConfigWithFullConfig(): void
    {
        $config = [
            'access_key' => 'test-access-key',
            'secret_key' => 'test-secret-key',
            'bucket' => 'test-bucket',
            'prefix' => 'test-prefix',
            'region' => 'cn-south-1',
            'endpoint' => 'custom.endpoint.com',
        ];

        $adapter = $this->factory->createFromConfig($config);

        // 验证完整配置创建正确适配器
        $this->assertInstanceOf(
            HuaweiObsAdapter::class,
            $adapter
        );
    }

    public function testCreateFromConfigWithPartialConfig(): void
    {
        $config = [
            'access_key' => 'test-access-key',
            'secret_key' => 'test-secret-key',
            'bucket' => 'test-bucket',
            'prefix' => 'test-prefix',
            // 没有 region 和 endpoint
        ];

        $adapter = $this->factory->createFromConfig($config);

        // 验证部分配置也创建正确适配器
        $this->assertInstanceOf(
            HuaweiObsAdapter::class,
            $adapter
        );
    }
}
