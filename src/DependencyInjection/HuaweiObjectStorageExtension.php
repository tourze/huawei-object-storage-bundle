<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class HuaweiObjectStorageExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
