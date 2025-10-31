<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\HuaweiObjectStorageBundle\HuaweiObjectStorageBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(HuaweiObjectStorageBundle::class)]
#[RunTestsInSeparateProcesses]
final class HuaweiObjectStorageBundleTest extends AbstractBundleTestCase
{
}
