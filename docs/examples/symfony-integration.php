<?php

declare(strict_types=1);

/**
 * 华为OBS Flysystem适配器 Symfony 集成示例
 *
 * 本示例展示如何在 Symfony 应用中使用装饰器模式自动集成华为OBS
 */

use Tourze\FileStorageBundle\Factory\FilesystemFactoryInterface;

// 示例：在 Symfony 控制器中使用
class ExampleController
{
    public function __construct(
        private FilesystemFactoryInterface $filesystemFactory,
    ) {
    }

    public function uploadAction(): Response
    {
        // 当 OBS 环境变量配置完整时，这会自动返回华为 OBS 文件系统
        // 当 OBS 配置不完整时，这会返回本地文件系统
        $filesystem = $this->filesystemFactory->createFilesystem();

        // 写入文件 - 根据配置自动保存到 OBS 或本地
        $filesystem->write('uploads/document.pdf', $pdfContent);

        // 读取文件
        $content = $filesystem->read('uploads/document.pdf');

        // 生成公共 URL（如果支持）
        if (method_exists($filesystem, 'publicUrl')) {
            $url = $filesystem->publicUrl('uploads/document.pdf');
        }

        return new Response('File uploaded successfully');
    }

    public function listFilesAction(): JsonResponse
    {
        $filesystem = $this->filesystemFactory->createFilesystem();

        // 列出目录内容
        $files = [];
        foreach ($filesystem->listContents('uploads') as $item) {
            $files[] = [
                'path' => $item->path(),
                'type' => $item instanceof FileAttributes ? 'file' : 'directory',
                'size' => $item instanceof FileAttributes ? $item->fileSize() : null,
                'lastModified' => $item instanceof FileAttributes ? $item->lastModified() : null,
            ];
        }

        return new JsonResponse($files);
    }
}

// 示例：在 Symfony 服务中使用
class DocumentService
{
    private FilesystemOperator $filesystem;

    public function __construct(FilesystemFactoryInterface $filesystemFactory)
    {
        // 自动获取配置的文件系统（本地或 OBS）
        $this->filesystem = $filesystemFactory->createFilesystem();
    }

    public function saveDocument(string $path, string $content): void
    {
        $this->filesystem->write($path, $content);
    }

    public function getDocument(string $path): string
    {
        return $this->filesystem->read($path);
    }

    public function deleteDocument(string $path): void
    {
        $this->filesystem->delete($path);
    }

    public function documentExists(string $path): bool
    {
        return $this->filesystem->fileExists($path);
    }
}

// 示例：在命令行命令中使用
class BackupCommand extends Command
{
    protected static $defaultName = 'app:backup';

    public function __construct(
        private FilesystemFactoryInterface $filesystemFactory,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filesystem = $this->filesystemFactory->createFilesystem();

        // 创建备份
        $backupPath = sprintf('backups/backup-%s.tar.gz', date('Y-m-d-H-i-s'));
        $filesystem->write($backupPath, $this->createBackup());

        $output->writeln(sprintf('Backup created: %s', $backupPath));

        // 清理旧备份
        $oneWeekAgo = time() - (7 * 24 * 60 * 60);
        foreach ($filesystem->listContents('backups') as $item) {
            if ($item instanceof FileAttributes && $item->lastModified() < $oneWeekAgo) {
                $filesystem->delete($item->path());
                $output->writeln(sprintf('Deleted old backup: %s', $item->path()));
            }
        }

        return Command::SUCCESS;
    }
}

// 示例：环境变量配置
// 在 .env 文件中设置：
// HUAWEI_OBS_ACCESS_KEY=your-access-key
// HUAWEI_OBS_SECRET_KEY=your-secret-key
// HUAWEI_OBS_BUCKET=your-bucket
// HUAWEI_OBS_REGION=cn-north-4  # 可选
// HUAWEI_OBS_PREFIX=my-app/     # 可选

// 注意：装饰器会在运行时读取 $_ENV 并自动检测 OBS 配置
// 配置完整时使用 OBS，否则使用本地存储
