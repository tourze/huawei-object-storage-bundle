<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\FileStorageBundle\FileStorageBundle;

class HuaweiObjectStorageBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            FileStorageBundle::class => ['all' => true],
        ];
    }
}
