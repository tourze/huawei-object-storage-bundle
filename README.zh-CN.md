# huawei-object-storage-bundle

[English](README.md) | [中文](README.zh-CN.md)

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![构建状态](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?branch=master&style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo?style=flat-square)](https://codecov.io/gh/tourze/php-monorepo)

华为OBS（对象存储服务）的 Flysystem 适配器实现。

## 目录

- [功能特性](#功能特性)
- [系统要求](#系统要求)
- [安装](#安装)
- [配置](#配置)
- [快速开始](#快速开始)
- [支持的操作](#支持的操作)
- [高级用法](#高级用法)
- [安全性](#安全性)
- [注意事项](#注意事项)
- [测试](#测试)
- [贡献](#贡献)
- [许可证](#许可证)

## 功能特性

- 完整实现 Flysystem v3 FilesystemAdapter 接口
- 支持基本文件操作（读取、写入、删除、复制、移动）
- 支持目录操作（虚拟目录）
- 文件元数据获取（大小、MIME类型、最后修改时间）
- 大文件流操作
- 分段上传支持
- 签名 v1 认证
- 通过环境变量配置

## 系统要求

此包需要：

- PHP 8.1 或更高版本
- Symfony 6.4 或更高版本
- League Flysystem 3.10 或更高版本

额外依赖：
- `symfony/http-client`: ^6.4
- `tourze/file-storage-bundle`: self.version

测试依赖：
- `phpunit/phpunit`: ^10.0

## 安装

### 通过 Composer 安装

```bash
composer require tourze/huawei-object-storage-bundle
```

## 配置

此 Bundle 会自动装饰 `FilesystemFactory` 以支持华为 OBS。装饰器会自动检测 
OBS 配置并在配置可用时使用。

设置以下环境变量以启用 OBS 存储：

```bash
# OBS 存储必需配置
HUAWEI_OBS_ACCESS_KEY=你的访问密钥
HUAWEI_OBS_SECRET_KEY=你的密钥
HUAWEI_OBS_BUCKET=你的桶名称

# 可选
HUAWEI_OBS_REGION=cn-north-4  # 默认值
HUAWEI_OBS_ENDPOINT=obs.cn-north-4.myhuaweicloud.com  # 默认值
HUAWEI_OBS_PREFIX=  # 路径前缀，默认为空
```

当所有必需的 OBS 环境变量都设置后，Bundle 会自动使用 OBS 存储。否则，
会回退到本地存储。

所有配置通过 `$_ENV` 在运行时读取 - 不使用配置文件。

## 快速开始

### 自动集成

Bundle 会自动装饰 `FilesystemFactory` 服务。当 OBS 配置完整时，任何使用 
`FilesystemFactory` 的服务都会自动获得华为 OBS 存储：

```php
use Tourze\FileStorageBundle\Factory\FilesystemFactoryInterface;

// 当 OBS 配置完整时，这会自动返回 OBS 文件系统
$filesystem = $container->get(FilesystemFactoryInterface::class)
    ->createFilesystem();

// 写入文件
$filesystem->write('path/to/file.txt', 'Hello World');

// 读取文件
$content = $filesystem->read('path/to/file.txt');

// 删除文件
$filesystem->delete('path/to/file.txt');

// 列出内容
$listing = $filesystem->listContents('path/to/directory');
```

### 直接使用

你也可以直接使用 OBS 适配器：

```php
use League\Flysystem\Filesystem;
use Tourze\HuaweiObjectStorageBundle\Factory\ObsAdapterFactory;

// 使用运行时配置通过工厂创建适配器
$factory = $container->get(ObsAdapterFactory::class);
$adapter = $factory->create(
    $_ENV['HUAWEI_OBS_ACCESS_KEY'],
    $_ENV['HUAWEI_OBS_SECRET_KEY'],
    $_ENV['HUAWEI_OBS_BUCKET'],
    $_ENV['HUAWEI_OBS_PREFIX'] ?? '',
    $_ENV['HUAWEI_OBS_REGION'] ?? null,
    $_ENV['HUAWEI_OBS_ENDPOINT'] ?? null
);

// 创建 Flysystem 实例
$filesystem = new Filesystem($adapter);
```

## 支持的操作

- **文件操作**：读取、写入、删除、复制、移动
- **目录操作**：创建、删除、列出内容
- **元数据**：文件大小、最后修改时间、MIME类型
- **流操作**：读取流、写入流
- **分段上传**：大文件上传

## 高级用法

### 使用流操作

```php
// 使用流写入
$stream = fopen('local/file.txt', 'r');
$filesystem->writeStream('remote/file.txt', $stream);

// 读取为流
$stream = $filesystem->readStream('remote/file.txt');
$content = stream_get_contents($stream);
```

### 文件元数据

```php
// 获取文件大小
$size = $filesystem->fileSize('path/to/file.txt');

// 获取最后修改时间
$timestamp = $filesystem->lastModified('path/to/file.txt');

// 获取 MIME 类型
$mimeType = $filesystem->mimeType('path/to/file.txt');
```

## 安全性

### 凭据管理

- **绝不将凭据存储在配置文件中**
- 始终使用环境变量处理敏感数据
- 使用华为云 IAM 进行访问密钥管理
- 定期轮换访问密钥
- 为 OBS 访问使用最小必要权限

### 访问控制

- 适当配置桶策略
- 所有通信使用 HTTPS
- 考虑为有限访问使用临时凭据
- 监控访问日志中的可疑活动

## 注意事项

1. 所有配置通过 `$_ENV` 数组在运行时读取
2. 使用 `@AsDecorator` 属性实现装饰器模式的自动集成
3. 配置文件中不使用环境变量 - 一切都在运行时处理
4. 华为OBS不支持通过ACL修改文件可见性
5. OBS中的目录是虚拟的，通过对象键前缀模拟
6. 建议使用路径前缀来组织文件结构

## 测试

```bash
./vendor/bin/phpunit packages/huawei-object-storage-bundle/tests
```

## 贡献

详情请参见 [CONTRIBUTING.md](CONTRIBUTING.md)。

## 许可证

MIT 许可证（MIT）。详情请参见[许可证文件](LICENSE)。