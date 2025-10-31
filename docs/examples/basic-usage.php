<?php

declare(strict_types=1);

/**
 * 华为OBS Flysystem适配器基本使用示例
 *
 * 本示例展示如何使用华为OBS适配器进行基本的文件操作
 */

use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;
use Tourze\HuaweiObjectStorageBundle\Factory\ObsAdapterFactory;

// 设置环境变量（生产环境中应该通过.env文件或系统环境变量设置）
// 当这些环境变量都设置后，装饰器会自动使用 OBS 存储
$_ENV['HUAWEI_OBS_ACCESS_KEY'] = 'your-access-key';
$_ENV['HUAWEI_OBS_SECRET_KEY'] = 'your-secret-key';
$_ENV['HUAWEI_OBS_BUCKET'] = 'your-bucket-name';
$_ENV['HUAWEI_OBS_REGION'] = 'cn-north-4';  // 可选

// 创建HTTP客户端
$httpClient = HttpClient::create();

// 创建适配器工厂
$factory = new ObsAdapterFactory($httpClient);

// 从 $_ENV 运行时读取配置创建适配器
$adapter = $factory->create(
    $_ENV['HUAWEI_OBS_ACCESS_KEY'],
    $_ENV['HUAWEI_OBS_SECRET_KEY'],
    $_ENV['HUAWEI_OBS_BUCKET'],
    $_ENV['HUAWEI_OBS_PREFIX'] ?? '',
    $_ENV['HUAWEI_OBS_REGION'] ?? null,
    $_ENV['HUAWEI_OBS_ENDPOINT'] ?? null
);

// 创建Flysystem实例
$filesystem = new Filesystem($adapter);

// ========== 文件操作示例 ==========

// 1. 写入文件
echo "1. 写入文件\n";
$filesystem->write('test/hello.txt', 'Hello, Huawei OBS!');
echo "文件写入成功\n\n";

// 2. 读取文件
echo "2. 读取文件\n";
$content = $filesystem->read('test/hello.txt');
echo "文件内容: {$content}\n\n";

// 3. 检查文件是否存在
echo "3. 检查文件是否存在\n";
$exists = $filesystem->fileExists('test/hello.txt');
echo '文件存在: ' . ($exists ? '是' : '否') . "\n\n";

// 4. 获取文件元数据
echo "4. 获取文件元数据\n";
$size = $filesystem->fileSize('test/hello.txt');
echo "文件大小: {$size} 字节\n";

$lastModified = $filesystem->lastModified('test/hello.txt');
echo '最后修改时间: ' . date('Y-m-d H:i:s', $lastModified) . "\n";

$mimeType = $filesystem->mimeType('test/hello.txt');
echo "MIME类型: {$mimeType}\n\n";

// 5. 复制文件
echo "5. 复制文件\n";
$filesystem->copy('test/hello.txt', 'test/hello-copy.txt');
echo "文件复制成功\n\n";

// 6. 移动文件
echo "6. 移动文件\n";
$filesystem->move('test/hello-copy.txt', 'test/hello-moved.txt');
echo "文件移动成功\n\n";

// 7. 列出目录内容
echo "7. 列出目录内容\n";
$listing = $filesystem->listContents('test');
foreach ($listing as $item) {
    $type = $item instanceof FileAttributes ? '文件' : '目录';
    echo "- {$item->path()} ({$type})\n";
}
echo "\n";

// 8. 使用流写入大文件
echo "8. 使用流写入大文件\n";
$stream = fopen('php://temp', 'r+');
fwrite($stream, str_repeat('大文件内容', 1000));
rewind($stream);
$filesystem->writeStream('test/large-file.txt', $stream);
fclose($stream);
echo "大文件写入成功\n\n";

// 9. 使用流读取文件
echo "9. 使用流读取文件\n";
$stream = $filesystem->readStream('test/large-file.txt');
$firstLine = fgets($stream);
fclose($stream);
echo '文件第一行: ' . substr($firstLine, 0, 50) . "...\n\n";

// 10. 删除文件
echo "10. 删除文件\n";
$filesystem->delete('test/hello.txt');
$filesystem->delete('test/hello-moved.txt');
$filesystem->delete('test/large-file.txt');
echo "文件删除成功\n\n";

// ========== 目录操作示例 ==========

// 11. 创建目录
echo "11. 创建目录\n";
$filesystem->createDirectory('test/subdirectory');
echo "目录创建成功\n\n";

// 12. 检查目录是否存在
echo "12. 检查目录是否存在\n";
$dirExists = $filesystem->directoryExists('test/subdirectory');
echo '目录存在: ' . ($dirExists ? '是' : '否') . "\n\n";

// 13. 在子目录中创建文件
echo "13. 在子目录中创建文件\n";
$filesystem->write('test/subdirectory/file1.txt', '子目录文件1');
$filesystem->write('test/subdirectory/file2.txt', '子目录文件2');
echo "子目录文件创建成功\n\n";

// 14. 递归列出目录内容
echo "14. 递归列出目录内容\n";
$listing = $filesystem->listContents('test', true);
foreach ($listing as $item) {
    $type = $item instanceof FileAttributes ? '文件' : '目录';
    echo "- {$item->path()} ({$type})\n";
}
echo "\n";

// 15. 删除目录（包括其中的文件）
echo "15. 删除目录\n";
$filesystem->deleteDirectory('test/subdirectory');
echo "目录删除成功\n\n";

// ========== 高级用法示例 ==========

// 16. 使用自定义配置创建适配器
echo "16. 使用自定义配置\n";
$customAdapter = $factory->create(
    'custom-access-key',
    'custom-secret-key',
    'custom-bucket',
    'my-app/', // 所有操作都会在这个前缀下进行
    'cn-east-3',
    null
);
$customFilesystem = new Filesystem($customAdapter);
echo "自定义配置的适配器创建成功\n\n";

// 17. 带元数据写入文件
echo "17. 带元数据写入文件\n";
$config = [
    'ContentType' => 'application/json',
    'metadata' => [
        'author' => 'John Doe',
        'version' => '1.0',
    ],
];
$filesystem->write('test/metadata.json', '{"key": "value"}', $config);
echo "带元数据的文件写入成功\n\n";

// 清理测试文件
$filesystem->delete('test/metadata.json');

echo "示例执行完成！\n";
