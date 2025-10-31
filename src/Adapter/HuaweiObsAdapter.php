<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\Adapter;

use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Tourze\HuaweiObjectStorageBundle\Client\ObsClientInterface;
use Tourze\HuaweiObjectStorageBundle\Exception\ConfigurationException;
use Tourze\HuaweiObjectStorageBundle\Exception\ObsException;

/**
 * 华为OBS适配器，实现Flysystem接口
 *
 * 该适配器实现了League\Flysystem\FilesystemAdapter接口，
 * 提供了华为OBS的文件系统操作功能。
 *
 * 主要功能：
 * - 文件操作：读取、写入、删除、复制、移动
 * - 目录操作：创建、删除、列举（虚拟目录）
 * - 元数据操作：获取文件大小、MIME类型、最后修改时间
 * - 流操作：支持大文件的流式读写
 *
 * 注意事项：
 * - OBS中的目录是虚拟的，通过对象键的前缀来模拟
 * - 不支持通过ACL修改文件可见性（visibility）
 * - 使用PathPrefixer来处理路径前缀
 *
 * @see https://flysystem.thephpleague.com/docs/adapter/creating-an-adapter/
 */
class HuaweiObsAdapter implements FilesystemAdapter
{
    private PathPrefixer $prefixer;

    public function __construct(
        private readonly ObsClientInterface $client,
        private readonly string $bucket,
        string $prefix = '',
    ) {
        if ('' === $this->bucket) {
            throw new ConfigurationException('Bucket name cannot be empty');
        }

        $this->prefixer = new PathPrefixer($prefix);
    }

    public function fileExists(string $path): bool
    {
        $location = $this->prefixer->prefixPath($path);

        try {
            $this->client->headObject($this->bucket, $location);

            return true;
        } catch (ObsException|TransportExceptionInterface|\RuntimeException $e) {
            return false;
        }
    }

    public function directoryExists(string $path): bool
    {
        $location = $this->prefixer->prefixDirectoryPath($path);

        // OBS中目录是虚拟的，我们通过列出以该路径为前缀的对象来判断
        // 参考：在OBS中，目录是通过对象键的前缀来模拟的
        try {
            $result = $this->client->listObjects($this->bucket, [
                'prefix' => $location,
                'max-keys' => 1,
            ]);

            return [] !== $result['Contents'];
        } catch (ObsException|TransportExceptionInterface|\RuntimeException $e) {
            return false;
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $location = $this->prefixer->prefixPath($path);

        try {
            $options = $this->extractOptionsFromConfig($config);
            $this->client->putObject($this->bucket, $location, $contents, $options);
        } catch (ObsException|TransportExceptionInterface|\RuntimeException $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $location = $this->prefixer->prefixPath($path);

        try {
            $options = $this->extractOptionsFromConfig($config);
            $body = stream_get_contents($contents);

            if (false === $body) {
                throw new ObsException('Unable to read stream contents');
            }

            $this->client->putObject($this->bucket, $location, $body, $options);
        } catch (ObsException|TransportExceptionInterface|\RuntimeException $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function read(string $path): string
    {
        $location = $this->prefixer->prefixPath($path);

        try {
            $result = $this->client->getObject($this->bucket, $location);

            return $result['Body'];
        } catch (ObsException|TransportExceptionInterface|\RuntimeException $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }
    }

    public function readStream(string $path)
    {
        $location = $this->prefixer->prefixPath($path);

        try {
            $result = $this->client->getObject($this->bucket, $location);
            $stream = fopen('php://temp', 'r+');

            if (false === $stream) {
                throw new ObsException('Unable to create temp stream');
            }

            fwrite($stream, $result['Body']);
            rewind($stream);

            return $stream;
        } catch (ObsException|TransportExceptionInterface|\RuntimeException $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }
    }

    public function delete(string $path): void
    {
        $location = $this->prefixer->prefixPath($path);

        try {
            $this->client->deleteObject($this->bucket, $location);
        } catch (ObsException|TransportExceptionInterface|\RuntimeException $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function deleteDirectory(string $path): void
    {
        $location = $this->prefixer->prefixDirectoryPath($path);

        try {
            // 先列出目录下所有对象
            $objects = [];
            $marker = null;

            do {
                $result = $this->client->listObjects($this->bucket, [
                    'prefix' => $location,
                    'marker' => $marker,
                ]);

                if ([] !== $result['Contents']) {
                    foreach ($result['Contents'] as $object) {
                        $objects[] = ['Key' => $object['Key']];
                    }
                }

                $marker = $result['NextMarker'] ?? null;
            } while (null !== $marker);

            // 批量删除对象
            if ([] !== $objects) {
                $this->client->deleteObjects($this->bucket, $objects);
            }
        } catch (ObsException|TransportExceptionInterface|\RuntimeException $e) {
            throw UnableToDeleteDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        // OBS中目录是虚拟的，创建一个以/结尾的空对象表示目录
        // 参考文档：packages/huawei-object-storage-bundle/API参考/基本概念.md
        // 在OBS中，目录实际上是一个以"/"结尾的零字节对象
        $location = $this->prefixer->prefixDirectoryPath($path);

        try {
            $this->client->putObject($this->bucket, $location, '');
        } catch (ObsException|TransportExceptionInterface|\RuntimeException $e) {
            throw UnableToCreateDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        // 华为OBS不支持通过单个对象ACL修改可见性
        // 参考文档：packages/huawei-object-storage-bundle/API参考/设置对象ACL.md
        // 注意：虽然OBS支持对象ACL，但Flysystem的visibility概念与OBS的ACL模型不完全匹配
        throw UnableToSetVisibility::atLocation($path, 'Huawei OBS does not support visibility changes through ACL.');
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes(
            $path,
            null,
            Visibility::PRIVATE
        );
    }

    public function mimeType(string $path): FileAttributes
    {
        $location = $this->prefixer->prefixPath($path);

        try {
            $result = $this->client->headObject($this->bucket, $location);
            $mimeType = $result['ContentType'] ?? null;

            return new FileAttributes(
                $path,
                null,
                null,
                null,
                $mimeType
            );
        } catch (ObsException|TransportExceptionInterface|\RuntimeException $e) {
            throw UnableToRetrieveMetadata::mimeType($path, $e->getMessage(), $e);
        }
    }

    public function lastModified(string $path): FileAttributes
    {
        $location = $this->prefixer->prefixPath($path);

        try {
            $result = $this->client->headObject($this->bucket, $location);
            $lastModified = isset($result['LastModified'])
                ? strtotime($result['LastModified'])
                : null;

            return new FileAttributes(
                $path,
                null,
                null,
                false !== $lastModified ? $lastModified : null
            );
        } catch (ObsException|TransportExceptionInterface|\RuntimeException $e) {
            throw UnableToRetrieveMetadata::lastModified($path, $e->getMessage(), $e);
        }
    }

    public function fileSize(string $path): FileAttributes
    {
        $location = $this->prefixer->prefixPath($path);

        try {
            $result = $this->client->headObject($this->bucket, $location);
            $fileSize = isset($result['ContentLength'])
                ? (int) $result['ContentLength']
                : null;

            return new FileAttributes(
                $path,
                $fileSize
            );
        } catch (ObsException|TransportExceptionInterface|\RuntimeException $e) {
            throw UnableToRetrieveMetadata::fileSize($path, $e->getMessage(), $e);
        }
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $location = $this->prefixer->prefixDirectoryPath($path);
        $marker = null;

        // 使用OBS的列举对象接口
        // 参考文档：packages/huawei-object-storage-bundle/API参考/列举桶内对象.md
        do {
            try {
                $options = $this->buildListObjectsOptions($location, $marker, $deep);
                $result = $this->client->listObjects($this->bucket, $options);

                yield from $this->processObjectContents($result);
                yield from $this->processDirectoryPrefixes($result, $deep);

                $marker = $result['NextMarker'] ?? null;
            } catch (ObsException|TransportExceptionInterface|\RuntimeException $e) {
                throw new ObsException('Unable to list contents: ' . $e->getMessage(), 0, $e);
            }
        } while (null !== $marker);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildListObjectsOptions(string $location, ?string $marker, bool $deep): array
    {
        $options = [
            'prefix' => $location,
            'marker' => $marker,
        ];

        if (!$deep) {
            // 使用delimiter参数实现非递归列举
            // delimiter用于对对象名进行分组，通常使用'/'
            $options['delimiter'] = '/';
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $result
     * @return iterable<FileAttributes>
     */
    private function processObjectContents(array $result): iterable
    {
        // 处理文件
        // Contents包含了匹配前缀的所有对象
        if ([] === $result['Contents']) {
            return;
        }

        foreach ($result['Contents'] as $object) {
            $objectPath = $this->prefixer->stripPrefix($object['Key']);

            // 跳过目录标记对象（以'/' 结尾的对象）
            if (str_ends_with($object['Key'], '/')) {
                continue;
            }

            $lastModified = isset($object['LastModified']) ? strtotime($object['LastModified']) : null;
            yield new FileAttributes(
                $objectPath,
                isset($object['Size']) ? (int) $object['Size'] : null,
                null,
                false !== $lastModified ? $lastModified : null
            );
        }
    }

    /**
     * @param array<string, mixed> $result
     * @return iterable<DirectoryAttributes>
     */
    private function processDirectoryPrefixes(array $result, bool $deep): iterable
    {
        // 处理目录（仅在非递归模式下）
        // CommonPrefixes包含了被delimiter分隔的公共前缀
        if ($deep || [] === $result['CommonPrefixes']) {
            return;
        }

        foreach ($result['CommonPrefixes'] as $prefix) {
            $directoryPath = $this->prefixer->stripPrefix($prefix['Prefix']);
            yield new DirectoryAttributes(rtrim($directoryPath, '/'));
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->copy($source, $destination, $config);
            $this->delete($source);
        } catch (ObsException|TransportExceptionInterface|\RuntimeException|UnableToCopyFile|UnableToDeleteFile $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $sourceLocation = $this->prefixer->prefixPath($source);
        $destinationLocation = $this->prefixer->prefixPath($destination);

        try {
            $options = $this->extractOptionsFromConfig($config);
            // 使用OBS的复制对象接口
            // 参考文档：packages/huawei-object-storage-bundle/API参考/复制对象.md
            // 注意：复制操作是在服务端进行的，不需要下载再上传
            $this->client->copyObject(
                $this->bucket,
                $sourceLocation,
                $this->bucket,
                $destinationLocation,
                $options
            );
        } catch (ObsException|TransportExceptionInterface|\RuntimeException $e) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function extractOptionsFromConfig(Config $config): array
    {
        $options = [];

        $contentType = $config->get('ContentType');
        if (null !== $contentType) {
            $options['ContentType'] = $contentType;
        }

        $metadata = $config->get('metadata');
        if (is_array($metadata)) {
            foreach ($metadata as $key => $value) {
                $options['x-obs-meta-' . $key] = $value;
            }
        }

        return $options;
    }
}
