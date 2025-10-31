<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\Tests\Adapter;

use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\HuaweiObjectStorageBundle\Adapter\HuaweiObsAdapter;
use Tourze\HuaweiObjectStorageBundle\Client\ObsClientInterface;
use Tourze\HuaweiObjectStorageBundle\Tests\Adapter\TestObsClient;

/**
 * @internal
 */
#[CoversClass(HuaweiObsAdapter::class)]
final class HuaweiObsAdapterTest extends TestCase
{
    private TestObsClient $mockClient;

    private HuaweiObsAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = new TestObsClient();

        $this->adapter = new HuaweiObsAdapter(
            $this->mockClient,
            'test-bucket',
            'test-prefix'
        );
    }

    public function testFileExists(): void
    {
        $this->mockClient->setNextReturn(['StatusCode' => 200]);

        $this->assertTrue($this->adapter->fileExists('test.txt'));

        // 验证调用参数
        $this->assertEquals('headObject', $this->mockClient->lastCall['method']);
        $this->assertEquals(['test-bucket', 'test-prefix/test.txt'], $this->mockClient->lastCall['args']);
    }

    public function testFileNotExists(): void
    {
        $this->mockClient->setNextException(new \RuntimeException('Not found'));

        $this->assertFalse($this->adapter->fileExists('test.txt'));

        // 验证调用参数
        $this->assertEquals('headObject', $this->mockClient->lastCall['method']);
        $this->assertEquals(['test-bucket', 'test-prefix/test.txt'], $this->mockClient->lastCall['args']);
    }

    public function testWrite(): void
    {
        $this->mockClient->setNextReturn(['StatusCode' => 200]);

        $this->adapter->write('test.txt', 'test content', new Config());

        // 验证调用参数
        $this->assertEquals('putObject', $this->mockClient->lastCall['method']);
        $this->assertEquals(['test-bucket', 'test-prefix/test.txt', 'test content', []], $this->mockClient->lastCall['args']);
    }

    public function testWriteFailure(): void
    {
        $this->mockClient->setNextException(new \RuntimeException('Write failed'));

        $this->expectException(UnableToWriteFile::class);
        $this->adapter->write('test.txt', 'test content', new Config());
    }

    public function testRead(): void
    {
        $this->mockClient->setNextReturn(['Body' => 'test content']);

        $content = $this->adapter->read('test.txt');
        $this->assertEquals('test content', $content);

        // 验证调用参数
        $this->assertEquals('getObject', $this->mockClient->lastCall['method']);
        $this->assertEquals(['test-bucket', 'test-prefix/test.txt', []], $this->mockClient->lastCall['args']);
    }

    public function testDelete(): void
    {
        $this->mockClient->setNextReturn([]);

        $this->adapter->delete('test.txt');

        // 验证调用参数
        $this->assertEquals('deleteObject', $this->mockClient->lastCall['method']);
        $this->assertEquals(['test-bucket', 'test-prefix/test.txt'], $this->mockClient->lastCall['args']);
    }

    public function testListContents(): void
    {
        $this->mockClient->setNextReturn([
            'Contents' => [
                [
                    'Key' => 'test-prefix/folder/file1.txt',
                    'Size' => 100,
                    'LastModified' => '2023-01-01T00:00:00Z',
                ],
                [
                    'Key' => 'test-prefix/folder/file2.txt',
                    'Size' => 200,
                    'LastModified' => '2023-01-02T00:00:00Z',
                ],
            ],
            'NextMarker' => null,
        ]);

        $contents = iterator_to_array($this->adapter->listContents('folder', true));

        $this->assertCount(2, $contents);
        $this->assertInstanceOf(FileAttributes::class, $contents[0]);
        $this->assertEquals('folder/file1.txt', $contents[0]->path());
        $this->assertEquals(100, $contents[0]->fileSize());

        // 验证调用参数
        $this->assertEquals('listObjects', $this->mockClient->lastCall['method']);
        $expectedArgs = ['test-bucket', [
            'prefix' => 'test-prefix/folder/',
            'marker' => null,
        ]];
        $this->assertEquals($expectedArgs, $this->mockClient->lastCall['args']);
    }

    public function testCopy(): void
    {
        $this->mockClient->setNextReturn([]);

        $this->adapter->copy('source.txt', 'destination.txt', new Config());

        // 验证copy操作调用了正确的方法
        $this->assertEquals('copyObject', $this->mockClient->lastCall['method']);
    }

    public function testMove(): void
    {
        // 对于多个调用，我们简化测试，只验证方法被调用
        $this->mockClient->setNextReturn([]);

        $this->adapter->move('source.txt', 'destination.txt', new Config());

        // 验证最后一次调用是deleteObject
        $this->assertEquals('deleteObject', $this->mockClient->lastCall['method']);
    }

    public function testFileSize(): void
    {
        $this->mockClient->setNextReturn(['ContentLength' => '1024']);

        $attributes = $this->adapter->fileSize('test.txt');
        $this->assertEquals(1024, $attributes->fileSize());
    }

    public function testMimeType(): void
    {
        $this->mockClient->setNextReturn(['ContentType' => 'text/plain']);

        $attributes = $this->adapter->mimeType('test.txt');
        $this->assertEquals('text/plain', $attributes->mimeType());
    }

    public function testLastModified(): void
    {
        $this->mockClient->setNextReturn(['LastModified' => '2023-01-01T00:00:00Z']);

        $attributes = $this->adapter->lastModified('test.txt');
        $this->assertEquals(strtotime('2023-01-01T00:00:00Z'), $attributes->lastModified());
    }

    public function testCreateDirectory(): void
    {
        $this->mockClient->setNextReturn(['StatusCode' => 200]);

        $this->adapter->createDirectory('test-dir', new Config());

        // 验证没有异常抛出，说明目录创建成功
        $this->assertTrue(true);
    }

    public function testCreateDirectoryFailure(): void
    {
        $this->mockClient->setNextException(new \RuntimeException('Create failed'));

        $this->expectException(UnableToCreateDirectory::class);
        $this->adapter->createDirectory('test-dir', new Config());
    }

    public function testDeleteDirectory(): void
    {
        $this->mockClient->setNextReturn([
            'Contents' => [
                ['Key' => 'test-prefix/test-dir/file1.txt'],
                ['Key' => 'test-prefix/test-dir/file2.txt'],
            ],
            'NextMarker' => null,
        ]);

        $this->adapter->deleteDirectory('test-dir');

        // 验证没有异常抛出，说明目录删除成功
        $this->assertTrue(true);
    }

    public function testDeleteDirectoryFailure(): void
    {
        $this->mockClient->setNextException(new \RuntimeException('List failed'));

        $this->expectException(UnableToDeleteDirectory::class);
        $this->adapter->deleteDirectory('test-dir');
    }

    public function testDirectoryExists(): void
    {
        $this->mockClient->setNextReturn([
            'Contents' => [
                ['Key' => 'test-prefix/test-dir/file1.txt'],
            ],
        ]);

        $this->assertTrue($this->adapter->directoryExists('test-dir'));
    }

    public function testDirectoryNotExists(): void
    {
        $this->mockClient->setNextReturn(['Contents' => []]);

        $this->assertFalse($this->adapter->directoryExists('test-dir'));
    }

    public function testReadStream(): void
    {
        $this->mockClient->setNextReturn(['Body' => 'test content']);

        $result = $this->adapter->readStream('test.txt');
        $this->assertIsResource($result);

        rewind($result);
        $this->assertEquals('test content', stream_get_contents($result));
        fclose($result);
    }

    public function testReadStreamFailure(): void
    {
        $this->mockClient->setNextException(new \RuntimeException('Read failed'));

        $this->expectException(UnableToReadFile::class);
        $this->adapter->readStream('test.txt');
    }

    public function testWriteStream(): void
    {
        $stream = fopen('php://memory', 'r+');
        if (false === $stream) {
            self::fail('Failed to create stream');
        }
        fwrite($stream, 'test content');
        rewind($stream);

        $this->mockClient->setNextReturn(['StatusCode' => 200]);

        $this->adapter->writeStream('test.txt', $stream, new Config());
        fclose($stream);

        // 验证调用参数
        $this->assertEquals('putObject', $this->mockClient->lastCall['method']);
        $this->assertEquals(['test-bucket', 'test-prefix/test.txt', 'test content', []], $this->mockClient->lastCall['args']);
    }

    public function testWriteStreamFailure(): void
    {
        $stream = fopen('php://memory', 'r+');
        if (false === $stream) {
            self::fail('Failed to create stream');
        }
        fwrite($stream, 'test content');
        rewind($stream);

        $this->mockClient->setNextException(new \RuntimeException('Write failed'));

        $this->expectException(UnableToWriteFile::class);
        $this->adapter->writeStream('test.txt', $stream, new Config());
        fclose($stream);
    }

    public function testVisibility(): void
    {
        $attributes = $this->adapter->visibility('test.txt');
        $this->assertEquals(Visibility::PRIVATE, $attributes->visibility());
    }
}
