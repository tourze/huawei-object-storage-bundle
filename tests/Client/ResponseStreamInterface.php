<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\Tests\Client;

use Symfony\Contracts\HttpClient\ResponseStreamInterface as SymfonyResponseStreamInterface;

/**
 * 测试用的响应流接口实现
 *
 * @internal
 */
interface ResponseStreamInterface extends SymfonyResponseStreamInterface
{
}
