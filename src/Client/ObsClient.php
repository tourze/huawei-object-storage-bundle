<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\Client;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\HuaweiObjectStorageBundle\Exception\ConfigurationException;
use Tourze\HuaweiObjectStorageBundle\Exception\ObsException;
use Tourze\HuaweiObjectStorageBundle\Signature\ObsSignature;

/**
 * 华为OBS客户端
 *
 * 封装与华为OBS API的HTTP通信
 * 参考文档：packages/huawei-object-storage-bundle/API参考/
 *
 * @see https://support.huaweicloud.com/api-obs/
 */
class ObsClient implements ObsClientInterface
{
    private ObsSignature $signature;

    private string $region;

    private string $endpoint;

    private LoggerInterface $logger;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        string $accessKey,
        string $secretKey,
        ?string $region = null,
        ?string $endpoint = null,
        ?LoggerInterface $logger = null,
    ) {
        // 使用提供的配置或默认值
        $this->region = $region ?? 'cn-north-4';
        $this->endpoint = $endpoint ?? sprintf('obs.%s.myhuaweicloud.com', $this->region);

        if ('' === $accessKey || '' === $secretKey) {
            throw new ConfigurationException('Missing access key or secret key');
        }

        $this->signature = new ObsSignature($accessKey, $secretKey);
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * 创建桶
     *
     * 参考文档：packages/huawei-object-storage-bundle/API参考/创建桶.md
     *
     * @see packages/huawei-object-storage-bundle/API参考/创建桶-1.md
     *
     * @param string $bucket  桶名称
     * @param array  $options 可选参数
     *                        - Location: 桶的区域位置
     *                        - StorageClass: 存储类型 (STANDARD/WARM/COLD)
     *                        - ACL: 访问控制策略 (private/public-read/public-read-write)
     *
     * @return array 响应结果
     */
    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function createBucket(string $bucket, array $options = []): array
    {
        $headers = [];
        $body = '';

        if (isset($options['Location'])) {
            $body = sprintf(
                '<CreateBucketConfiguration xmlns="http://obs.%s.myhuaweicloud.com/doc/2015-06-30/"><Location>%s</Location></CreateBucketConfiguration>',
                $this->region,
                $options['Location']
            );
            $headers['Content-Type'] = 'application/xml';
        }

        if (isset($options['StorageClass'])) {
            $headers['x-obs-storage-class'] = $options['StorageClass'];
        }

        if (isset($options['ACL'])) {
            $headers['x-obs-acl'] = $options['ACL'];
        }

        return $this->request('PUT', $bucket, '', [], $headers, $body);
    }

    /**
     * 删除桶
     *
     * 参考文档：packages/huawei-object-storage-bundle/API参考/删除桶.md
     * 注意：删除之前需要确保桶内无对象
     *
     * @param string $bucket 桶名称
     *
     * @return array 响应结果
     */
    /**
     * @return array<string, mixed>
     */
    public function deleteBucket(string $bucket): array
    {
        return $this->request('DELETE', $bucket, '');
    }

    /**
     * 获取桶列表
     *
     * 参考文档：packages/huawei-object-storage-bundle/API参考/获取桶列表.md
     *
     * @see packages/huawei-object-storage-bundle/API参考/获取桶列表-0.md
     *
     * @return array 返回桶列表，包含桶名称和创建时间
     */
    /**
     * @return array<string, mixed>
     */
    public function listBuckets(): array
    {
        $response = $this->request('GET', '', '');

        // 解析XML响应
        $xml = simplexml_load_string($response['Body']);
        $buckets = [];

        if (isset($xml->Buckets->Bucket)) {
            foreach ($xml->Buckets->Bucket as $bucket) {
                $buckets[] = [
                    'Name' => (string) $bucket->Name,
                    'CreationDate' => (string) $bucket->CreationDate,
                ];
            }
        }

        return ['Buckets' => $buckets];
    }

    /**
     * 上传对象
     *
     * 参考文档：packages/huawei-object-storage-bundle/API参考/PUT上传.md
     *
     * @param string $bucket  桶名称
     * @param string $object  对象名称
     * @param string $content 对象内容
     * @param array  $headers HTTP头部，可包含：
     *                        - Content-Type: MIME类型
     *                        - x-obs-acl: 访问控制
     *                        - x-obs-storage-class: 存储类型
     *                        - x-obs-meta-*: 自定义元数据
     *
     * @return array 响应结果
     */
    /**
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    public function putObject(string $bucket, string $object, string $content, array $headers = []): array
    {
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/octet-stream';
        }

        return $this->request('PUT', $bucket, $object, [], $headers, $content);
    }

    /**
     * 获取对象
     *
     * 参考文档：packages/huawei-object-storage-bundle/API参考/下载对象.md
     *
     * @param string $bucket 桶名称
     * @param string $object 对象名称
     * @param array  $query  查询参数，如versionId
     *
     * @return array 响应结果，包含'Body'键存储对象内容
     */
    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function getObject(string $bucket, string $object, array $query = []): array
    {
        return $this->request('GET', $bucket, $object, $query);
    }

    /**
     * 删除对象
     *
     * 参考文档：packages/huawei-object-storage-bundle/API参考/删除对象.md
     *
     * @param string $bucket 桶名称
     * @param string $object 对象名称
     *
     * @return array 响应结果
     */
    /**
     * @return array<string, mixed>
     */
    public function deleteObject(string $bucket, string $object): array
    {
        return $this->request('DELETE', $bucket, $object);
    }

    /**
     * 批量删除对象
     *
     * 参考文档：packages/huawei-object-storage-bundle/API参考/批量删除对象.md
     *
     * @param string $bucket  桶名称
     * @param array  $objects 要删除的对象列表，每个元素包含：
     *                        - Key: 对象键
     *                        - VersionId: 版本号（可选）
     *
     * @return array 响应结果
     */
    /**
     * @param array<array<string, mixed>> $objects
     * @return array<string, mixed>
     */
    public function deleteObjects(string $bucket, array $objects): array
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<Delete>';

        foreach ($objects as $object) {
            $xml .= '<Object>';
            $xml .= '<Key>' . htmlspecialchars($object['Key'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</Key>';
            if (isset($object['VersionId'])) {
                $xml .= '<VersionId>' . htmlspecialchars($object['VersionId'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</VersionId>';
            }
            $xml .= '</Object>';
        }

        $xml .= '</Delete>';

        $headers = [
            'Content-Type' => 'application/xml',
            'Content-MD5' => base64_encode(md5($xml, true)),
        ];

        return $this->request('POST', $bucket, '', ['delete' => ''], $headers, $xml);
    }

    /**
     * 获取对象元数据
     *
     * 参考文档：packages/huawei-object-storage-bundle/API参考/获取对象元数据.md
     * 使用HEAD方法获取对象元数据，不返回对象内容
     *
     * @param string $bucket 桶名称
     * @param string $object 对象名称
     *
     * @return array 响应头部，包含ContentLength、ContentType、LastModified等
     */
    /**
     * @return array<string, mixed>
     */
    public function headObject(string $bucket, string $object): array
    {
        return $this->request('HEAD', $bucket, $object);
    }

    /**
     * 列举对象
     *
     * 参考文档：packages/huawei-object-storage-bundle/API参考/列举桶内对象.md
     *
     * @param string $bucket 桶名称
     * @param array  $query  查询参数：
     *                       - prefix: 列出指定前缀的对象
     *                       - delimiter: 对对象名分组的字符
     *                       - marker: 列举对象的起始位置
     *                       - max-keys: 列举对象的最大数目
     *
     * @return array 返回对象列表和公共前缀
     */
    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function listObjects(string $bucket, array $query = []): array
    {
        $response = $this->request('GET', $bucket, '', $query);

        // 解析XML响应
        $xml = simplexml_load_string($response['Body']);
        if (false === $xml) {
            throw new ObsException('Failed to parse XML response');
        }
        $result = [
            'Name' => (string) $xml->Name,
            'Prefix' => (string) $xml->Prefix,
            'MaxKeys' => (int) $xml->MaxKeys,
            'IsTruncated' => 'true' === (string) $xml->IsTruncated,
            'Contents' => [],
            'CommonPrefixes' => [],
        ];

        if (isset($xml->NextMarker)) {
            $result['NextMarker'] = (string) $xml->NextMarker;
        }

        if (isset($xml->Contents)) {
            foreach ($xml->Contents as $content) {
                $result['Contents'][] = [
                    'Key' => (string) $content->Key,
                    'LastModified' => (string) $content->LastModified,
                    'ETag' => (string) $content->ETag,
                    'Size' => (int) $content->Size,
                    'StorageClass' => (string) $content->StorageClass,
                ];
            }
        }

        if (isset($xml->CommonPrefixes)) {
            foreach ($xml->CommonPrefixes as $prefix) {
                $result['CommonPrefixes'][] = [
                    'Prefix' => (string) $prefix->Prefix,
                ];
            }
        }

        return $result;
    }

    /**
     * 复制对象
     *
     * 参考文档：packages/huawei-object-storage-bundle/API参考/复制对象.md
     *
     * @param string $sourceBucket 源桶名称
     * @param string $sourceObject 源对象名称
     * @param string $destBucket   目标桶名称
     * @param string $destObject   目标对象名称
     * @param array  $headers      可选HTTP头部
     *
     * @return array 响应结果
     */
    /**
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    public function copyObject(string $sourceBucket, string $sourceObject, string $destBucket, string $destObject, array $headers = []): array
    {
        $headers['x-obs-copy-source'] = sprintf('/%s/%s', $sourceBucket, $sourceObject);

        return $this->request('PUT', $destBucket, $destObject, [], $headers);
    }

    /**
     * 初始化分段上传
     *
     * 参考文档：packages/huawei-object-storage-bundle/API参考/初始化上传段任务.md
     *
     * @param string $bucket  桶名称
     * @param string $object  对象名称
     * @param array  $headers HTTP头部
     *
     * @return array 返回包含UploadId的响应
     */
    /**
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    public function initiateMultipartUpload(string $bucket, string $object, array $headers = []): array
    {
        $response = $this->request('POST', $bucket, $object, ['uploads' => ''], $headers);

        // 解析XML响应
        $xml = simplexml_load_string($response['Body']);
        if (false === $xml) {
            throw new ObsException('Failed to parse XML response');
        }

        return [
            'Bucket' => (string) $xml->Bucket,
            'Key' => (string) $xml->Key,
            'UploadId' => (string) $xml->UploadId,
        ];
    }

    /**
     * 上传段
     *
     * 参考文档：packages/huawei-object-storage-bundle/API参考/上传段.md
     *
     * @param string $bucket     桶名称
     * @param string $object     对象名称
     * @param string $uploadId   分段上传任务ID
     * @param int    $partNumber 段号（从1开始）
     * @param string $content    段内容
     *
     * @return array 返回包含ETag的响应
     */
    /**
     * @return array<string, mixed>
     */
    public function uploadPart(string $bucket, string $object, string $uploadId, int $partNumber, string $content): array
    {
        $query = [
            'partNumber' => (string) $partNumber,
            'uploadId' => $uploadId,
        ];

        $response = $this->request('PUT', $bucket, $object, $query, [], $content);

        return [
            'ETag' => $response['Headers']['etag'][0] ?? '',
        ];
    }

    /**
     * 完成分段上传
     *
     * 参考文档：packages/huawei-object-storage-bundle/API参考/合并段.md
     *
     * @param string $bucket   桶名称
     * @param string $object   对象名称
     * @param string $uploadId 分段上传任务ID
     * @param array  $parts    已上传的段列表，每个元素包含：
     *                         - PartNumber: 段号
     *                         - ETag: 段的ETag值
     *
     * @return array 响应结果
     */
    /**
     * @param array<array<string, mixed>> $parts
     * @return array<string, mixed>
     */
    public function completeMultipartUpload(string $bucket, string $object, string $uploadId, array $parts): array
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<CompleteMultipartUpload>';

        foreach ($parts as $part) {
            $xml .= '<Part>';
            $xml .= '<PartNumber>' . $part['PartNumber'] . '</PartNumber>';
            $xml .= '<ETag>' . htmlspecialchars($part['ETag'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</ETag>';
            $xml .= '</Part>';
        }

        $xml .= '</CompleteMultipartUpload>';

        $headers = [
            'Content-Type' => 'application/xml',
        ];

        $query = ['uploadId' => $uploadId];

        return $this->request('POST', $bucket, $object, $query, $headers, $xml);
    }

    /**
     * 取消分段上传
     *
     * 参考文档：packages/huawei-object-storage-bundle/API参考/取消多段上传任务.md
     *
     * @param string $bucket   桶名称
     * @param string $object   对象名称
     * @param string $uploadId 分段上传任务ID
     *
     * @return array 响应结果
     */
    /**
     * @return array<string, mixed>
     */
    public function abortMultipartUpload(string $bucket, string $object, string $uploadId): array
    {
        $query = ['uploadId' => $uploadId];

        return $this->request('DELETE', $bucket, $object, $query);
    }

    /**
     * 发送HTTP请求
     *
     * 统一处理所有OBS API请求：
     * 1. 构建URL和路径
     * 2. 添加签名和必要的头部
     * 3. 发送请求并处理响应
     *
     * @param string $method  HTTP方法
     * @param string $bucket  桶名称
     * @param string $object  对象名称
     * @param array  $query   查询参数
     * @param array  $headers HTTP头部
     * @param string $body    请求体
     *
     * @return array 返回状态码、头部和响应体
     *
     * @throws ObsException 当请求失败时
     */
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    private function request(string $method, string $bucket, string $object, array $query = [], array $headers = [], string $body = ''): array
    {
        // 构建URL
        $host = '' !== $bucket ? sprintf('%s.%s', $bucket, $this->endpoint) : $this->endpoint;
        $path = '' !== $object ? '/' . ltrim($object, '/') : '/';
        $url = sprintf('https://%s%s', $host, $path);

        if ([] !== $query) {
            $url .= '?' . http_build_query($query);
        }

        // 准备日期
        $date = gmdate('D, d M Y H:i:s \G\M\T');
        $headers['Date'] = $date;
        $headers['Host'] = $host;

        // 计算签名
        $signatureHeaders = $this->signature->signRequest(
            $method,
            $bucket,
            $object,
            $query,
            $headers,
            $body
        );

        // 合并签名头
        $headers = array_merge($headers, $signatureHeaders);

        // 发送请求
        $startTime = microtime(true);

        // 记录请求日志
        $this->logger->info('OBS API Request', [
            'method' => $method,
            'url' => $url,
            'bucket' => $bucket,
            'object' => $object,
            'query_params' => $query,
            'headers' => array_keys($headers), // 只记录header键名，避免敏感信息
            'body_size' => strlen($body),
        ]);

        try {
            // @audit-logged 外部系统交互：华为OBS API调用
            $response = $this->httpClient->request($method, $url, [
                'headers' => $headers,
                'body' => $body,
            ]);

            $statusCode = $response->getStatusCode();
            $responseHeaders = $response->getHeaders();
            $responseBody = $response->getContent();

            $duration = microtime(true) - $startTime;

            // 记录成功响应日志
            $this->logger->info('OBS API Response', [
                'method' => $method,
                'url' => $url,
                'status_code' => $statusCode,
                'response_size' => strlen($responseBody),
                'duration_ms' => round($duration * 1000, 2),
                'success' => true,
            ]);

            if ($statusCode >= 300) {
                $this->logger->error('OBS API Error Response', [
                    'method' => $method,
                    'url' => $url,
                    'status_code' => $statusCode,
                    'response_body' => $responseBody,
                    'duration_ms' => round($duration * 1000, 2),
                ]);
                throw new ObsException(sprintf('Request failed with status %d: %s', $statusCode, $responseBody));
            }

            return [
                'StatusCode' => $statusCode,
                'Headers' => $responseHeaders,
                'Body' => $responseBody,
            ];
        } catch (TransportExceptionInterface $e) {
            $duration = microtime(true) - $startTime;

            // 记录异常日志
            $this->logger->error('OBS API Transport Error', [
                'method' => $method,
                'url' => $url,
                'error_message' => $e->getMessage(),
                'duration_ms' => round($duration * 1000, 2),
                'success' => false,
            ]);

            throw new ObsException('HTTP request failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
