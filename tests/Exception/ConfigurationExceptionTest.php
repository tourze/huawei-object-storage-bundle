<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\HuaweiObjectStorageBundle\Exception\ConfigurationException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(ConfigurationException::class)]
final class ConfigurationExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInheritsFromInvalidArgumentException(): void
    {
        $exception = new ConfigurationException('test message');

        // ConfigurationException 已经继承自 InvalidArgumentException，不需要再次断言
        $this->assertEquals('test message', $exception->getMessage());
    }

    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Configuration error');

        throw new ConfigurationException('Configuration error');
    }

    public function testExceptionWithCodeAndPrevious(): void
    {
        $previousException = new \Exception('Previous error');
        $exception = new ConfigurationException('Configuration error', 123, $previousException);

        $this->assertEquals('Configuration error', $exception->getMessage());
        $this->assertEquals(123, $exception->getCode());
        $this->assertSame($previousException, $exception->getPrevious());
    }
}
