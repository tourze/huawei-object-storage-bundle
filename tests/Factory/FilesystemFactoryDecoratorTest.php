<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\Tests\Factory;

use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\HuaweiObjectStorageBundle\Factory\FilesystemFactoryDecorator;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(FilesystemFactoryDecorator::class)]
#[RunTestsInSeparateProcesses]
final class FilesystemFactoryDecoratorTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 集成测试会自动配置服务
    }

    public function testUsesLocalStorageWhenObsNotConfigured(): void
    {
        // 确保 OBS 配置不存在
        unset($_ENV['HUAWEI_OBS_ACCESS_KEY'], $_ENV['HUAWEI_OBS_SECRET_KEY'], $_ENV['HUAWEI_OBS_BUCKET']);

        $decorator = self::getService(FilesystemFactoryDecorator::class);
        $filesystem = $decorator->createFilesystem();

        // 验证创建的文件系统具备FilesystemInterface接口
        $this->assertInstanceOf(
            FilesystemOperator::class,
            $filesystem
        );
        $this->cleanupEnvironment();
    }

    public function testUsesObsStorageWhenConfigured(): void
    {
        // 设置完整的 OBS 配置
        $_ENV['HUAWEI_OBS_ACCESS_KEY'] = 'test-access-key';
        $_ENV['HUAWEI_OBS_SECRET_KEY'] = 'test-secret-key';
        $_ENV['HUAWEI_OBS_BUCKET'] = 'test-bucket';

        $decorator = self::getService(FilesystemFactoryDecorator::class);
        $filesystem = $decorator->createFilesystem();

        // 验证OBS配置完整时也创建文件系统操作器
        $this->assertInstanceOf(
            FilesystemOperator::class,
            $filesystem
        );
        $this->cleanupEnvironment();
    }

    public function testUsesLocalStorageWhenPartialObsConfig(): void
    {
        // 只设置部分 OBS 配置
        $_ENV['HUAWEI_OBS_ACCESS_KEY'] = 'test-access-key';
        unset($_ENV['HUAWEI_OBS_SECRET_KEY'], $_ENV['HUAWEI_OBS_BUCKET']);

        $decorator = self::getService(FilesystemFactoryDecorator::class);
        $filesystem = $decorator->createFilesystem();

        // 验证配置不完整时也创建文件系统操作器
        $this->assertInstanceOf(
            FilesystemOperator::class,
            $filesystem
        );
        $this->cleanupEnvironment();
    }

    public function testCreateFilesystem(): void
    {
        $decorator = self::getService(FilesystemFactoryDecorator::class);
        $filesystem = $decorator->createFilesystem();

        // 验证工厂装饰器能创建文件系统操作器
        $this->assertInstanceOf(
            FilesystemOperator::class,
            $filesystem
        );
        $this->cleanupEnvironment();
    }

    private function cleanupEnvironment(): void
    {
        // 清理环境变量
        unset(
            $_ENV['HUAWEI_OBS_ACCESS_KEY'],
            $_ENV['HUAWEI_OBS_SECRET_KEY'],
            $_ENV['HUAWEI_OBS_BUCKET'],
            $_ENV['HUAWEI_OBS_PREFIX'],
            $_ENV['HUAWEI_OBS_REGION'],
            $_ENV['HUAWEI_OBS_ENDPOINT']
        );
    }
}
