<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\Signature;

/**
 * 华为OBS签名计算类
 *
 * 实现华为OBS的签名v1认证算法
 * 参考文档：packages/huawei-object-storage-bundle/API参考/Header中携带签名.md
 *
 * @see https://support.huaweicloud.com/api-obs/obs_04_0010.html
 */
class ObsSignature
{
    private const OBS_PREFIX = 'x-obs-';

    /**
     * OBS支持的子资源列表
     * 参考文档：packages/huawei-object-storage-bundle/API参考/Header中携带签名.md#表1-构造StringToSign所需参数说明
     */
    private const SUB_RESOURCES = [
        'CDNNotifyConfiguration', 'acl', 'append', 'attname', 'backtosource', 'cors', 'customdomain', 'delete',
        'deletebucket', 'directcoldaccess', 'encryption', 'inventory', 'length', 'lifecycle', 'location', 'logging',
        'metadata', 'modify', 'name', 'notification', 'partNumber', 'policy', 'position', 'quota', 'rename',
        'replication', 'response-cache-control', 'response-content-disposition', 'response-content-encoding',
        'response-content-language', 'response-content-type', 'response-expires', 'restore', 'storageClass',
        'storagePolicy', 'storageinfo', 'tagging', 'torrent', 'truncate', 'uploadId', 'uploads', 'versionId',
        'versioning', 'versions', 'website', 'x-image-process', 'x-image-save-bucket', 'x-image-save-object',
        'x-obs-security-token', 'object-lock', 'retention',
    ];

    public function __construct(
        private readonly string $accessKey,
        private readonly string $secretKey,
    ) {
    }

    /**
     * 签名请求
     *
     * 根据OBS签名算法生成Authorization头
     * 签名格式：Authorization: OBS AccessKeyID:signature
     *
     * @param string $method  HTTP方法（GET/PUT/POST/DELETE等）
     * @param string $bucket  桶名称
     * @param string $object  对象名称
     * @param array  $query   查询参数
     * @param array  $headers HTTP头部
     * @param string $body    请求体（用于计算Content-MD5）
     *
     * @return array 返回包含Authorization头的数组
     */
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $headers
     * @return array<string, string>
     */
    public function signRequest(
        string $method,
        string $bucket,
        string $object,
        array $query,
        array $headers,
        string $body,
    ): array {
        // 构造规范化的请求字符串
        $stringToSign = $this->stringToSign($method, $bucket, $object, $query, $headers);

        // 计算签名
        $signature = $this->calculateSignature($stringToSign);

        // 返回授权头
        return [
            'Authorization' => sprintf('OBS %s:%s', $this->accessKey, $signature),
        ];
    }

    /**
     * 构造待签名字符串
     *
     * 根据OBS签名规范构造StringToSign：
     * StringToSign = HTTP-Verb + "\n" +
     *                Content-MD5 + "\n" +
     *                Content-Type + "\n" +
     *                Date + "\n" +
     *                CanonicalizedHeaders + CanonicalizedResource
     *
     * 参考文档：packages/huawei-object-storage-bundle/API参考/Header中携带签名.md#请求字符串构造规则
     */
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $headers
     */
    private function stringToSign(
        string $method,
        string $bucket,
        string $object,
        array $query,
        array $headers,
    ): string {
        $contentMd5 = '';
        $contentType = '';
        $date = '';
        $canonicalizedHeaders = [];

        // 处理头部
        foreach ($headers as $key => $value) {
            $key = strtolower(trim($key));

            if ('content-md5' === $key) {
                $contentMd5 = $value;
                continue;
            }

            if ('content-type' === $key) {
                $contentType = $value;
                continue;
            }

            if ('date' === $key) {
                $date = $value;
                continue;
            }

            if (str_starts_with($key, self::OBS_PREFIX)) {
                $canonicalizedHeaders[$key] = trim($value);
            }
        }

        // 如果有x-obs-date，则Date为空
        // 参考文档：当有自定义字段x-obs-date时，Date参数按照空字符串处理
        if (isset($canonicalizedHeaders['x-obs-date'])) {
            $date = '';
        }

        // 构造StringToSign
        $stringToSign = $method . "\n";
        $stringToSign .= $contentMd5 . "\n";
        $stringToSign .= $contentType . "\n";
        $stringToSign .= $date . "\n";

        // 添加规范化的OBS头部
        ksort($canonicalizedHeaders);
        foreach ($canonicalizedHeaders as $key => $value) {
            $stringToSign .= $key . ':' . $value . "\n";
        }

        // 添加规范化的资源
        $stringToSign .= $this->canonicalizedResource($bucket, $object, $query);

        return $stringToSign;
    }

    /**
     * 构造规范化的资源
     *
     * CanonicalizedResource表示HTTP请求所指定的OBS资源
     * 构造方式：<桶名+对象名>+[子资源1] + [子资源2] + ...
     *
     * 参考文档：packages/huawei-object-storage-bundle/API参考/Header中携带签名.md#CanonicalizedResource
     *
     * @param string $bucket 桶名称
     * @param string $object 对象名称
     * @param array  $query  查询参数（包含子资源）
     *
     * @return string 规范化的资源字符串
     */
    /**
     * @param array<string, mixed> $query
     */
    private function canonicalizedResource(string $bucket, string $object, array $query): string
    {
        $resource = $this->buildBasicResource($bucket, $object);
        $queryString = $this->buildSubResourcesQueryString($query);

        return $resource . $queryString;
    }

    private function buildBasicResource(string $bucket, string $object): string
    {
        $resource = '/';

        if ('' !== $bucket) {
            $resource .= $bucket . '/';
            if ('' !== $object) {
                $resource .= $this->urlEncode($object);
            }
        }

        return $resource;
    }

    /**
     * @param array<string, mixed> $query
     */
    private function buildSubResourcesQueryString(array $query): string
    {
        $subResources = $this->extractSubResources($query);

        if ([] === $subResources) {
            return '';
        }

        ksort($subResources);
        $parts = $this->formatSubResourceParts($subResources);

        return '?' . implode('&', $parts);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    private function extractSubResources(array $query): array
    {
        $subResources = [];
        foreach ($query as $key => $value) {
            if (in_array($key, self::SUB_RESOURCES, true)) {
                $subResources[$key] = $value;
            }
        }

        return $subResources;
    }

    /**
     * @param array<string, mixed> $subResources
     * @return array<string>
     */
    private function formatSubResourceParts(array $subResources): array
    {
        $parts = [];
        foreach ($subResources as $key => $value) {
            if ('' === $value || null === $value) {
                $parts[] = $key;
            } else {
                $parts[] = $key . '=' . $value;
            }
        }

        return $parts;
    }

    /**
     * URL编码
     *
     * OBS特定的URL编码规则：
     * - 使用rawurlencode进行编码
     * - 将%2F还原为/
     * - 将%20替换为+
     *
     * @param string $input 需要编码的字符串
     *
     * @return string 编码后的字符串
     */
    private function urlEncode(string $input): string
    {
        return str_replace(
            ['%2F', '%20'],
            ['/', '+'],
            rawurlencode($input)
        );
    }

    /**
     * 计算HMAC-SHA1签名
     *
     * 使用SK对StringToSign进行HMAC-SHA1签名计算，然后进行Base64编码
     * Signature = Base64( HMAC-SHA1( YourSecretAccessKeyID, UTF-8-Encoding-Of( StringToSign ) ) )
     *
     * 参考文档：packages/huawei-object-storage-bundle/API参考/Header中携带签名.md#签名计算方法
     *
     * @param string $stringToSign 待签名字符串
     *
     * @return string Base64编码后的签名
     */
    private function calculateSignature(string $stringToSign): string
    {
        $hash = hash_hmac('sha1', $stringToSign, $this->secretKey, true);

        return base64_encode($hash);
    }
}
