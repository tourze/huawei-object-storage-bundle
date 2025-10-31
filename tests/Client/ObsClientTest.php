<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\Tests\Client;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\HuaweiObjectStorageBundle\Client\ObsClient;
use Tourze\HuaweiObjectStorageBundle\Client\ObsClientInterface;
use Tourze\HuaweiObjectStorageBundle\Exception\ConfigurationException;
use Tourze\HuaweiObjectStorageBundle\Exception\ObsException;
use Tourze\HuaweiObjectStorageBundle\Tests\Client\TestHttpClient;
use Tourze\HuaweiObjectStorageBundle\Tests\Client\TestResponse;

/**
 * @internal
 */
#[CoversClass(ObsClient::class)]
final class ObsClientTest extends TestCase
{
    private TestHttpClient $mockHttpClient;

    private TestResponse $mockResponse;

    private ObsClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockHttpClient = new TestHttpClient();
        $this->mockResponse = new TestResponse();

        $this->client = new ObsClient(
            $this->mockHttpClient,
            'test-access-key',
            'test-secret-key',
            'cn-north-4'
        );
    }

    public function testClientImplementsObsClientInterface(): void
    {
        // 验证客户端是ObsClientInterface的实现
        $this->assertInstanceOf(
            ObsClientInterface::class,
            $this->client
        );
    }

    public function testConstructorWithDefaultRegion(): void
    {
        $client = new ObsClient(
            $this->mockHttpClient,
            'test-access-key',
            'test-secret-key'
        );

        // 验证构造成功并且是正确类型
        $this->assertInstanceOf(ObsClient::class, $client);
    }

    public function testConstructorWithCustomEndpoint(): void
    {
        $client = new ObsClient(
            $this->mockHttpClient,
            'test-access-key',
            'test-secret-key',
            'cn-south-1',
            'custom.endpoint.com'
        );

        // 验证自定义端点构造成功
        $this->assertInstanceOf(ObsClient::class, $client);
    }

    public function testConstructorThrowsExceptionForEmptyAccessKey(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Missing access key or secret key');

        new ObsClient(
            $this->mockHttpClient,
            '',
            'test-secret-key'
        );
    }

    public function testConstructorThrowsExceptionForEmptySecretKey(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Missing access key or secret key');

        new ObsClient(
            $this->mockHttpClient,
            'test-access-key',
            ''
        );
    }

    public function testCreateBucketWithBasicOptions(): void
    {
        $this->mockResponse->statusCode = 200;
        $this->mockResponse->headers = [];
        $this->mockResponse->content = '';
        $this->mockHttpClient->nextResponse = $this->mockResponse;

        $result = $this->client->createBucket('test-bucket');

        $this->assertArrayHasKey('StatusCode', $result);
        $this->assertEquals(200, $result['StatusCode']);
    }

    public function testCreateBucketWithAllOptions(): void
    {
        $this->mockResponse->statusCode = 200;
        $this->mockResponse->headers = [];
        $this->mockResponse->content = '';
        $this->mockHttpClient->nextResponse = $this->mockResponse;

        $options = [
            'Location' => 'cn-south-1',
            'StorageClass' => 'STANDARD',
            'ACL' => 'private',
        ];

        $result = $this->client->createBucket('test-bucket', $options);

        $this->assertArrayHasKey('StatusCode', $result);
        $this->assertEquals(200, $result['StatusCode']);
    }

    public function testDeleteBucket(): void
    {
        $this->mockResponse->statusCode = 204;
        $this->mockResponse->headers = [];
        $this->mockResponse->content = '';
        $this->mockHttpClient->nextResponse = $this->mockResponse;

        $result = $this->client->deleteBucket('test-bucket');

        $this->assertArrayHasKey('StatusCode', $result);
        $this->assertEquals(204, $result['StatusCode']);
    }

    public function testListBuckets(): void
    {
        $xmlResponse = '<?xml version="1.0" encoding="UTF-8"?>
            <ListAllMyBucketsResult>
                <Buckets>
                    <Bucket>
                        <Name>test-bucket1</Name>
                        <CreationDate>2023-01-01T00:00:00.000Z</CreationDate>
                    </Bucket>
                    <Bucket>
                        <Name>test-bucket2</Name>
                        <CreationDate>2023-01-02T00:00:00.000Z</CreationDate>
                    </Bucket>
                </Buckets>
            </ListAllMyBucketsResult>';

        $this->mockResponse->statusCode = 200;
        $this->mockResponse->headers = [];
        $this->mockResponse->content = $xmlResponse;
        $this->mockHttpClient->nextResponse = $this->mockResponse;

        $result = $this->client->listBuckets();

        $this->assertArrayHasKey('Buckets', $result);
        $this->assertCount(2, $result['Buckets']);
        $this->assertEquals('test-bucket1', $result['Buckets'][0]['Name']);
        $this->assertEquals('test-bucket2', $result['Buckets'][1]['Name']);
    }

    public function testPutObject(): void
    {
        $this->mockResponse->statusCode = 200;
        $this->mockResponse->headers = [];
        $this->mockResponse->content = '';
        $this->mockHttpClient->nextResponse = $this->mockResponse;

        $result = $this->client->putObject('test-bucket', 'test-object', 'test content');

        $this->assertArrayHasKey('StatusCode', $result);
        $this->assertEquals(200, $result['StatusCode']);
    }

    public function testPutObjectWithHeaders(): void
    {
        $this->mockResponse->statusCode = 200;
        $this->mockResponse->headers = [];
        $this->mockResponse->content = '';
        $this->mockHttpClient->nextResponse = $this->mockResponse;

        $headers = [
            'Content-Type' => 'text/plain',
            'x-obs-acl' => 'private',
        ];

        $result = $this->client->putObject('test-bucket', 'test-object', 'test content', $headers);

        $this->assertArrayHasKey('StatusCode', $result);
        $this->assertEquals(200, $result['StatusCode']);
    }

    public function testGetObject(): void
    {
        $this->mockResponse->statusCode = 200;
        $this->mockResponse->headers = [];
        $this->mockResponse->content = 'test content';
        $this->mockHttpClient->nextResponse = $this->mockResponse;

        $result = $this->client->getObject('test-bucket', 'test-object');

        $this->assertArrayHasKey('StatusCode', $result);
        $this->assertArrayHasKey('Body', $result);
        $this->assertEquals(200, $result['StatusCode']);
        $this->assertEquals('test content', $result['Body']);
    }

    public function testDeleteObject(): void
    {
        $this->mockResponse->statusCode = 204;
        $this->mockResponse->headers = [];
        $this->mockResponse->content = '';
        $this->mockHttpClient->nextResponse = $this->mockResponse;

        $result = $this->client->deleteObject('test-bucket', 'test-object');

        $this->assertArrayHasKey('StatusCode', $result);
        $this->assertEquals(204, $result['StatusCode']);
    }

    public function testHeadObject(): void
    {
        $this->mockResponse->statusCode = 200;
        $this->mockResponse->headers = [
            'content-length' => ['1024'],
            'content-type' => ['text/plain'],
            'last-modified' => ['Wed, 01 Jan 2023 00:00:00 GMT'],
        ];
        $this->mockResponse->content = '';
        $this->mockHttpClient->nextResponse = $this->mockResponse;

        $result = $this->client->headObject('test-bucket', 'test-object');

        $this->assertArrayHasKey('StatusCode', $result);
        $this->assertArrayHasKey('Headers', $result);
        $this->assertEquals(200, $result['StatusCode']);
    }

    public function testListObjects(): void
    {
        $xmlResponse = '<?xml version="1.0" encoding="UTF-8"?>
            <ListBucketResult>
                <Name>test-bucket</Name>
                <Prefix>test-</Prefix>
                <MaxKeys>1000</MaxKeys>
                <IsTruncated>false</IsTruncated>
                <Contents>
                    <Key>test-object1</Key>
                    <LastModified>2023-01-01T00:00:00.000Z</LastModified>
                    <ETag>"123456789"</ETag>
                    <Size>1024</Size>
                    <StorageClass>STANDARD</StorageClass>
                </Contents>
                <Contents>
                    <Key>test-object2</Key>
                    <LastModified>2023-01-02T00:00:00.000Z</LastModified>
                    <ETag>"987654321"</ETag>
                    <Size>2048</Size>
                    <StorageClass>STANDARD</StorageClass>
                </Contents>
            </ListBucketResult>';

        $this->mockResponse->statusCode = 200;
        $this->mockResponse->headers = [];
        $this->mockResponse->content = $xmlResponse;
        $this->mockHttpClient->nextResponse = $this->mockResponse;

        $result = $this->client->listObjects('test-bucket', ['prefix' => 'test-']);

        $this->assertArrayHasKey('Contents', $result);
        $this->assertCount(2, $result['Contents']);
        $this->assertEquals('test-object1', $result['Contents'][0]['Key']);
        $this->assertEquals(1024, $result['Contents'][0]['Size']);
    }

    public function testCopyObject(): void
    {
        $this->mockResponse->statusCode = 200;
        $this->mockResponse->headers = [];
        $this->mockResponse->content = '';
        $this->mockHttpClient->nextResponse = $this->mockResponse;

        $result = $this->client->copyObject(
            'source-bucket',
            'source-object',
            'dest-bucket',
            'dest-object'
        );

        $this->assertArrayHasKey('StatusCode', $result);
        $this->assertEquals(200, $result['StatusCode']);
    }

    public function testRequestFailureThrowsObsException(): void
    {
        $this->mockResponse->statusCode = 500;
        $this->mockResponse->content = 'Internal Server Error';
        $this->mockHttpClient->nextResponse = $this->mockResponse;

        $this->expectException(ObsException::class);
        $this->expectExceptionMessage('Request failed with status 500');

        $this->client->putObject('test-bucket', 'test-object', 'test content');
    }

    public function testDeleteObjects(): void
    {
        $xmlResponse = '<?xml version="1.0" encoding="UTF-8"?>
            <DeleteResult>
                <Deleted>
                    <Key>object1</Key>
                </Deleted>
                <Deleted>
                    <Key>object2</Key>
                </Deleted>
            </DeleteResult>';

        $this->mockResponse->statusCode = 200;
        $this->mockResponse->headers = [];
        $this->mockResponse->content = $xmlResponse;
        $this->mockHttpClient->nextResponse = $this->mockResponse;

        $result = $this->client->deleteObjects('test-bucket', [['Key' => 'object1'], ['Key' => 'object2']]);

        $this->assertArrayHasKey('StatusCode', $result);
        $this->assertArrayHasKey('Headers', $result);
        $this->assertArrayHasKey('Body', $result);
        $this->assertEquals(200, $result['StatusCode']);
    }

    public function testInitiateMultipartUpload(): void
    {
        $xmlResponse = '<?xml version="1.0" encoding="UTF-8"?>
            <InitiateMultipartUploadResult>
                <Bucket>test-bucket</Bucket>
                <Key>test-object</Key>
                <UploadId>upload-id-123</UploadId>
            </InitiateMultipartUploadResult>';

        $this->mockResponse->statusCode = 200;
        $this->mockResponse->headers = [];
        $this->mockResponse->content = $xmlResponse;
        $this->mockHttpClient->nextResponse = $this->mockResponse;

        $result = $this->client->initiateMultipartUpload('test-bucket', 'test-object');

        $this->assertArrayHasKey('Bucket', $result);
        $this->assertArrayHasKey('Key', $result);
        $this->assertArrayHasKey('UploadId', $result);
        $this->assertEquals('test-bucket', $result['Bucket']);
        $this->assertEquals('test-object', $result['Key']);
        $this->assertEquals('upload-id-123', $result['UploadId']);
    }

    public function testUploadPart(): void
    {
        $this->mockResponse->statusCode = 200;
        $this->mockResponse->headers = [
            'etag' => ['"part-etag-123"'],
        ];
        $this->mockResponse->content = '';
        $this->mockHttpClient->nextResponse = $this->mockResponse;

        $result = $this->client->uploadPart('test-bucket', 'test-object', 'upload-id-123', 1, 'part content');

        $this->assertArrayHasKey('ETag', $result);
        $this->assertEquals('"part-etag-123"', $result['ETag']);
    }

    public function testCompleteMultipartUpload(): void
    {
        $xmlResponse = '<?xml version="1.0" encoding="UTF-8"?>
            <CompleteMultipartUploadResult>
                <Location>https://test-bucket.obs.cn-north-4.myhuaweicloud.com/test-object</Location>
                <Bucket>test-bucket</Bucket>
                <Key>test-object</Key>
                <ETag>"final-etag-123"</ETag>
            </CompleteMultipartUploadResult>';

        $this->mockResponse->statusCode = 200;
        $this->mockResponse->headers = [];
        $this->mockResponse->content = $xmlResponse;
        $this->mockHttpClient->nextResponse = $this->mockResponse;

        $parts = [
            ['PartNumber' => 1, 'ETag' => '"part-etag-1"'],
            ['PartNumber' => 2, 'ETag' => '"part-etag-2"'],
        ];

        $result = $this->client->completeMultipartUpload('test-bucket', 'test-object', 'upload-id-123', $parts);

        $this->assertArrayHasKey('StatusCode', $result);
        $this->assertArrayHasKey('Headers', $result);
        $this->assertArrayHasKey('Body', $result);
        $this->assertEquals(200, $result['StatusCode']);
    }

    public function testAbortMultipartUpload(): void
    {
        $this->mockResponse->statusCode = 204;
        $this->mockResponse->headers = [];
        $this->mockResponse->content = '';
        $this->mockHttpClient->nextResponse = $this->mockResponse;

        $result = $this->client->abortMultipartUpload('test-bucket', 'test-object', 'upload-id-123');

        $this->assertArrayHasKey('StatusCode', $result);
        $this->assertEquals(204, $result['StatusCode']);
    }
}
