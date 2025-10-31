<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\Client;

/**
 * OBS 客户端接口
 *
 * 定义华为 OBS API 客户端的核心方法
 */
interface ObsClientInterface
{
    /**
     * 获取对象元数据
     * @return array<string, mixed>
     */
    public function headObject(string $bucket, string $object): array;

    /**
     * 上传对象
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    public function putObject(string $bucket, string $object, string $content, array $headers = []): array;

    /**
     * 下载对象
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function getObject(string $bucket, string $object, array $query = []): array;

    /**
     * 删除对象
     * @return array<string, mixed>
     */
    public function deleteObject(string $bucket, string $object): array;

    /**
     * 批量删除对象
     * @param array<array<string, mixed>> $objects
     * @return array<string, mixed>
     */
    public function deleteObjects(string $bucket, array $objects): array;

    /**
     * 列举对象
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function listObjects(string $bucket, array $query = []): array;

    /**
     * 复制对象
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    public function copyObject(string $sourceBucket, string $sourceObject, string $destBucket, string $destObject, array $headers = []): array;
}
