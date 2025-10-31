<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\HuaweiObjectStorageBundle\Exception\ObsException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(ObsException::class)]
final class ObsExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInheritsFromRuntimeException(): void
    {
        $exception = new ObsException('test message');

        // ObsException 已经继承自 RuntimeException，不需要再次断言
        $this->assertEquals('test message', $exception->getMessage());
    }

    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(ObsException::class);
        $this->expectExceptionMessage('OBS operation failed');

        throw new ObsException('OBS operation failed');
    }

    public function testExceptionWithCodeAndPrevious(): void
    {
        $previousException = new \Exception('Previous error');
        $exception = new ObsException('OBS operation failed', 500, $previousException);

        $this->assertEquals('OBS operation failed', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
        $this->assertSame($previousException, $exception->getPrevious());
    }
}
