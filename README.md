# huawei-object-storage-bundle

[English](README.md) | [中文](README.zh-CN.md)

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?branch=master&style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo?style=flat-square)](https://codecov.io/gh/tourze/php-monorepo)

Flysystem adapter for Huawei OBS (Object Storage Service).

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Quick Start](#quick-start)
- [Supported Operations](#supported-operations)
- [Advanced Usage](#advanced-usage)
- [Security](#security)
- [Notes](#notes)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

## Features

- Full implementation of Flysystem v3 FilesystemAdapter interface
- Support for basic file operations (read, write, delete, copy, move)
- Directory operations with virtual directory support
- File metadata retrieval (size, MIME type, last modified)
- Stream operations for large files
- Multipart upload support for large objects
- Signature v1 authentication
- Configurable via environment variables

## Requirements

This package requires:

- PHP 8.1 or higher
- Symfony 6.4 or higher 
- League Flysystem 3.10 or higher

Additional dependencies:
- `symfony/http-client`: ^6.4
- `tourze/file-storage-bundle`: self.version

For testing:
- `phpunit/phpunit`: ^10.0

## Installation

### Install via Composer

```bash
composer require tourze/huawei-object-storage-bundle
```

## Configuration

This bundle automatically decorates the `FilesystemFactory` to support 
Huawei OBS. The decorator automatically detects OBS configuration and uses 
it when available.

Set the following environment variables to enable OBS storage:

```bash
# Required for OBS storage
HUAWEI_OBS_ACCESS_KEY=your_access_key
HUAWEI_OBS_SECRET_KEY=your_secret_key
HUAWEI_OBS_BUCKET=your_bucket_name

# Optional
HUAWEI_OBS_REGION=cn-north-4  # Default
HUAWEI_OBS_ENDPOINT=obs.cn-north-4.myhuaweicloud.com  # Default
HUAWEI_OBS_PREFIX=  # Path prefix, default is empty
```

When all required OBS environment variables are set, the bundle automatically 
uses OBS storage. Otherwise, it falls back to local storage.

All configuration is read at runtime via `$_ENV` - no configuration files 
are used.

## Quick Start

### Automatic Integration

The bundle automatically decorates the `FilesystemFactory` service. When OBS 
is configured, any service that uses `FilesystemFactory` will automatically 
get Huawei OBS storage:

```php
use Tourze\FileStorageBundle\Factory\FilesystemFactoryInterface;

// This will automatically return OBS filesystem when OBS is configured
$filesystem = $container->get(FilesystemFactoryInterface::class)
    ->createFilesystem();

// Write file
$filesystem->write('path/to/file.txt', 'Hello World');

// Read file
$content = $filesystem->read('path/to/file.txt');

// Delete file
$filesystem->delete('path/to/file.txt');

// List contents
$listing = $filesystem->listContents('path/to/directory');
```

### Direct Usage

You can also use the OBS adapter directly:

```php
use League\Flysystem\Filesystem;
use Tourze\HuaweiObjectStorageBundle\Factory\ObsAdapterFactory;

// Create adapter using factory with runtime config
$factory = $container->get(ObsAdapterFactory::class);
$adapter = $factory->create(
    $_ENV['HUAWEI_OBS_ACCESS_KEY'],
    $_ENV['HUAWEI_OBS_SECRET_KEY'],
    $_ENV['HUAWEI_OBS_BUCKET'],
    $_ENV['HUAWEI_OBS_PREFIX'] ?? '',
    $_ENV['HUAWEI_OBS_REGION'] ?? null,
    $_ENV['HUAWEI_OBS_ENDPOINT'] ?? null
);

// Create Flysystem instance
$filesystem = new Filesystem($adapter);
```

## Supported Operations

- **File Operations**: read, write, delete, copy, move
- **Directory Operations**: create, delete, list contents
- **Metadata**: file size, last modified time, MIME type
- **Stream Operations**: read stream, write stream
- **Multipart Upload**: for large files

## Advanced Usage

### Working with Streams

```php
// Write using stream
$stream = fopen('local/file.txt', 'r');
$filesystem->writeStream('remote/file.txt', $stream);

// Read as stream
$stream = $filesystem->readStream('remote/file.txt');
$content = stream_get_contents($stream);
```

### File Metadata

```php
// Get file size
$size = $filesystem->fileSize('path/to/file.txt');

// Get last modified time
$timestamp = $filesystem->lastModified('path/to/file.txt');

// Get MIME type
$mimeType = $filesystem->mimeType('path/to/file.txt');
```

## Security

### Credentials Management

- **Never store credentials in configuration files**
- Always use environment variables for sensitive data
- Use Huawei Cloud IAM for access key management
- Regularly rotate access keys
- Use minimal required permissions for OBS access

### Access Control

- Configure bucket policies appropriately
- Use HTTPS for all communications
- Consider using temporary credentials for limited access
- Monitor access logs for suspicious activity

## Notes

1. All configuration is read at runtime via `$_ENV` arrays
2. The decorator pattern is used with `@AsDecorator` attribute for automatic 
   integration
3. No environment variables are used in configuration files - everything is 
   runtime
4. Huawei OBS does not support visibility changes through ACL
5. Directories in OBS are virtual, simulated through object key prefixes
6. Use path prefixes to organize file structure

## Testing

```bash
./vendor/bin/phpunit packages/huawei-object-storage-bundle/tests
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.