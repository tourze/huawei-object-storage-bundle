<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\Tests\Adapter;

use Tourze\HuaweiObjectStorageBundle\Client\ObsClientInterface;

/**
 * 测试用的ObsClient Mock类
 *
 * @internal
 */
final class TestObsClient implements ObsClientInterface
{
    /** @var array{method: string, args: array<mixed>} */
    public array $lastCall = ['method' => '', 'args' => []];

    /** @var array<string, mixed> */
    private array $nextReturn = [];

    private ?\Throwable $nextException = null;

    /** @param array<string, mixed> $return */
    public function setNextReturn(array $return): void
    {
        $this->nextReturn = $return;
    }

    /**
     * 设置下一次调用的异常
     */
    public function setNextException(\Throwable $exception): void
    {
        $this->nextException = $exception;
    }

    /** @param array<mixed> $args
     * @return array<string, mixed> */
    private function handleCall(string $method, array $args): array
    {
        $this->lastCall = ['method' => $method, 'args' => $args];

        if (null !== $this->nextException) {
            throw $this->nextException;
        }

        $result = $this->nextReturn;
        $this->nextReturn = [];

        return $result;
    }

    public function headObject(string $bucket, string $object): array
    {
        return $this->handleCall('headObject', [$bucket, $object]);
    }

    public function putObject(string $bucket, string $object, string $content, array $headers = []): array
    {
        return $this->handleCall('putObject', [$bucket, $object, $content, $headers]);
    }

    public function getObject(string $bucket, string $object, array $query = []): array
    {
        return $this->handleCall('getObject', [$bucket, $object, $query]);
    }

    public function deleteObject(string $bucket, string $object): array
    {
        return $this->handleCall('deleteObject', [$bucket, $object]);
    }

    public function deleteObjects(string $bucket, array $objects): array
    {
        return $this->handleCall('deleteObjects', [$bucket, $objects]);
    }

    public function listObjects(string $bucket, array $query = []): array
    {
        return $this->handleCall('listObjects', [$bucket, $query]);
    }

    public function copyObject(string $sourceBucket, string $sourceObject, string $destBucket, string $destObject, array $headers = []): array
    {
        return $this->handleCall('copyObject', [$sourceBucket, $sourceObject, $destBucket, $destObject, $headers]);
    }

    // ObsClientInterface 其他方法的空实现
    /** @param array<string, mixed> $options
     * @return array<string, mixed> */
    public function createBucket(string $bucket, array $options = []): array
    {
        return $this->handleCall('createBucket', [$bucket, $options]);
    }

    /** @return array<string, mixed> */
    public function deleteBucket(string $bucket): array
    {
        return $this->handleCall('deleteBucket', [$bucket]);
    }

    /** @return array<string, mixed> */
    public function listBuckets(): array
    {
        return $this->handleCall('listBuckets', []);
    }

    /** @param array<string, mixed> $options
     * @return array<string, mixed> */
    public function initiateMultipartUpload(string $bucket, string $object, array $options = []): array
    {
        return $this->handleCall('initiateMultipartUpload', [$bucket, $object, $options]);
    }

    /** @return array<string, mixed> */
    public function uploadPart(string $bucket, string $object, string $uploadId, int $partNumber, string $content): array
    {
        return $this->handleCall('uploadPart', [$bucket, $object, $uploadId, $partNumber, $content]);
    }

    /** @param array<mixed> $parts
     * @return array<string, mixed> */
    public function completeMultipartUpload(string $bucket, string $object, string $uploadId, array $parts): array
    {
        return $this->handleCall('completeMultipartUpload', [$bucket, $object, $uploadId, $parts]);
    }

    /** @return array<string, mixed> */
    public function abortMultipartUpload(string $bucket, string $object, string $uploadId): array
    {
        return $this->handleCall('abortMultipartUpload', [$bucket, $object, $uploadId]);
    }
}
