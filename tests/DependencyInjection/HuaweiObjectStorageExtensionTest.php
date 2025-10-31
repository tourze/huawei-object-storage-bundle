<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Tourze\HuaweiObjectStorageBundle\DependencyInjection\HuaweiObjectStorageExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(HuaweiObjectStorageExtension::class)]
final class HuaweiObjectStorageExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getContainer(): ContainerInterface
    {
        // 创建Mock容器
        $container = $this->createMock(ContainerInterface::class);

        // 设置容器行为
        $container->method('has')->willReturnCallback(function (string $id): bool {
            return 'kernel.bundles' === $id;
        });

        $container->method('getParameter')->willReturnCallback(function (string $name): array {
            if ('kernel.bundles' === $name) {
                return [
                    'HuaweiObjectStorageBundle' => 'Tourze\HuaweiObjectStorageBundle\HuaweiObjectStorageBundle',
                ];
            }

            return [];
        });

        $container->method('hasParameter')->willReturnCallback(function (string $name): bool {
            return 'kernel.bundles' === $name;
        });

        $container->method('initialized')->willReturn(true);

        return $container;
    }

    public function testBundleIsRegistered(): void
    {
        // 验证Bundle已正确注册
        $container = $this->getContainer();
        $bundles = $container->getParameter('kernel.bundles');
        $this->assertIsArray($bundles);
        $this->assertArrayHasKey('HuaweiObjectStorageBundle', $bundles);
    }

    public function testExtensionIsLoaded(): void
    {
        // 验证扩展类可以正确实例化
        $extension = new HuaweiObjectStorageExtension();
        $this->assertInstanceOf(HuaweiObjectStorageExtension::class, $extension);

        // 验证扩展的别名是正确的
        $expectedAlias = 'huawei_object_storage';
        $this->assertSame($expectedAlias, $extension->getAlias());
    }
}
