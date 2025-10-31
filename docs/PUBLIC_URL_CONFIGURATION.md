# 华为 OBS 公共 URL 配置指南

## 概述

本文档说明如何配置华为 OBS 适配器以支持生成公共访问 URL。支持两种场景：
1. 使用 OBS 原生域名访问
2. 使用 CDN 或自定义域名访问

## 功能说明

当使用华为 OBS 作为文件存储后端时，`FileService` 会自动尝试生成文件的公共访问 URL 并保存到 `File` 实体的 `publicUrl` 字段中。

## 配置方式

### 方式一：使用 CDN 或自定义域名（推荐）

当您配置了 CDN 加速或自定义域名时，使用 `HUAWEI_OBS_PUBLIC_DOMAIN` 配置：

```bash
# 必需的配置（用于上传）
HUAWEI_OBS_ACCESS_KEY=your-access-key
HUAWEI_OBS_SECRET_KEY=your-secret-key
HUAWEI_OBS_BUCKET=your-bucket-name
HUAWEI_OBS_ENDPOINT=obs.cn-north-4.myhuaweicloud.com

# 公共访问域名（用于生成访问 URL）
HUAWEI_OBS_PUBLIC_DOMAIN=cdn.example.com
# 或
HUAWEI_OBS_PUBLIC_DOMAIN=files.example.com

# 可选配置
HUAWEI_OBS_PREFIX=uploads
HUAWEI_OBS_REGION=cn-north-4
```

生成的 URL 格式：
```
https://cdn.example.com/uploads/2025/07/document.pdf
```

### 方式二：使用 OBS 原生域名

如果没有配置 `HUAWEI_OBS_PUBLIC_DOMAIN`，系统会使用 `HUAWEI_OBS_ENDPOINT` 生成 URL：

```bash
# 必需的配置
HUAWEI_OBS_ACCESS_KEY=your-access-key
HUAWEI_OBS_SECRET_KEY=your-secret-key
HUAWEI_OBS_BUCKET=your-bucket-name
HUAWEI_OBS_ENDPOINT=obs.cn-north-4.myhuaweicloud.com

# 可选配置
HUAWEI_OBS_PREFIX=uploads
```

生成的 URL 格式：
```
https://my-bucket.obs.cn-north-4.myhuaweicloud.com/uploads/2025/07/document.pdf
```

## 注意事项

1. **访问权限**：生成的 URL 仅在对象具有公共读权限时才能访问。请确保您的 OBS 桶策略允许公共访问，或者对特定对象设置了适当的 ACL。

2. **URL 编码**：文件路径会自动进行 URL 编码，但保留路径分隔符 `/`。

3. **前缀处理**：如果配置了 `HUAWEI_OBS_PREFIX`，它会自动添加到所有对象路径前面。

4. **错误处理**：如果未配置 `HUAWEI_OBS_ENDPOINT`，系统会记录警告日志但不会抛出异常，`publicUrl` 字段将保持为 `null`。

## 配置优先级

1. 如果配置了 `HUAWEI_OBS_PUBLIC_DOMAIN`，优先使用此域名生成 URL
2. 如果没有配置 `HUAWEI_OBS_PUBLIC_DOMAIN` 但配置了 `HUAWEI_OBS_ENDPOINT`，使用 OBS 原生格式
3. 如果都没有配置，不生成公共 URL，`publicUrl` 字段保持为 `null`

## 使用示例

### 示例一：使用 CDN 域名

```bash
# 配置
HUAWEI_OBS_ENDPOINT=obs.cn-north-4.myhuaweicloud.com
HUAWEI_OBS_PUBLIC_DOMAIN=cdn.example.com
HUAWEI_OBS_PREFIX=uploads
```

```php
// 上传文件
$file = $fileService->uploadFile($uploadedFile, $user);

// 获取公共 URL
$publicUrl = $file->getPublicUrl();
// 输出: https://cdn.example.com/uploads/2025/07/document.pdf
```

### 示例二：使用 OBS 原生域名

```bash
# 配置（不设置 PUBLIC_DOMAIN）
HUAWEI_OBS_ENDPOINT=obs.cn-north-4.myhuaweicloud.com
HUAWEI_OBS_PREFIX=uploads
```

```php
// 上传文件
$file = $fileService->uploadFile($uploadedFile, $user);

// 获取公共 URL
$publicUrl = $file->getPublicUrl();
// 输出: https://my-bucket.obs.cn-north-4.myhuaweicloud.com/uploads/2025/07/document.pdf
```

## 故障排除

如果遇到错误 "Unable to generate public url"：

1. 检查是否至少配置了 `HUAWEI_OBS_ENDPOINT` 或 `HUAWEI_OBS_PUBLIC_DOMAIN` 其中之一
2. 确保域名格式正确（不需要包含 `https://` 前缀，系统会自动添加）
3. 查看日志文件了解详细错误信息

## 最佳实践

1. **生产环境建议使用 CDN**：配置 `HUAWEI_OBS_PUBLIC_DOMAIN` 使用 CDN 域名，提高访问速度
2. **开发环境可以使用 OBS 原生域名**：只配置 `HUAWEI_OBS_ENDPOINT` 即可
3. **域名格式**：配置域名时不要包含协议前缀（`https://`）和路径，只需要域名部分