<?php

declare(strict_types=1);

namespace Tourze\HuaweiObjectStorageBundle\Tests\Signature;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\HuaweiObjectStorageBundle\Signature\ObsSignature;

/**
 * @internal
 */
#[CoversClass(ObsSignature::class)]
final class ObsSignatureTest extends TestCase
{
    private ObsSignature $signature;

    protected function setUp(): void
    {
        parent::setUp();

        $this->signature = new ObsSignature('testAccessKey', 'testSecretKey');
    }

    public function testSignRequestWithSimpleCase(): void
    {
        $headers = [
            'Date' => 'Sat, 12 Oct 2015 08:12:38 GMT',
        ];

        $result = $this->signature->signRequest(
            'GET',
            'bucket',
            'object.txt',
            [],
            $headers,
            ''
        );

        $this->assertArrayHasKey('Authorization', $result);
        $this->assertStringStartsWith('OBS testAccessKey:', $result['Authorization']);
    }

    public function testSignRequestWithContentType(): void
    {
        $headers = [
            'Date' => 'Mon, 14 Oct 2015 12:08:34 GMT',
            'Content-Type' => 'text/plain',
            'x-obs-acl' => 'public-read',
        ];

        $result = $this->signature->signRequest(
            'PUT',
            'bucket',
            'object.txt',
            [],
            $headers,
            'test content'
        );

        $this->assertArrayHasKey('Authorization', $result);
        $this->assertStringStartsWith('OBS testAccessKey:', $result['Authorization']);
    }

    public function testSignRequestWithSubResources(): void
    {
        $headers = [
            'Date' => 'Sat, 12 Oct 2015 08:12:38 GMT',
        ];

        $query = [
            'acl' => '',
        ];

        $result = $this->signature->signRequest(
            'GET',
            'bucket',
            'object.txt',
            $query,
            $headers,
            ''
        );

        $this->assertArrayHasKey('Authorization', $result);
        $this->assertStringStartsWith('OBS testAccessKey:', $result['Authorization']);
    }

    public function testSignRequestWithMultipleHeaders(): void
    {
        $headers = [
            'Date' => 'Tue, 15 Oct 2015 07:20:09 GMT',
            'x-obs-date' => 'Tue, 15 Oct 2015 07:20:09 GMT',
            'x-obs-security-token' => 'YwkaRTbdY8g7q....',
            'Content-Type' => 'text/plain',
        ];

        $result = $this->signature->signRequest(
            'PUT',
            'bucket',
            'object.txt',
            [],
            $headers,
            ''
        );

        $this->assertArrayHasKey('Authorization', $result);
        $this->assertStringStartsWith('OBS testAccessKey:', $result['Authorization']);
    }

    public function testSignRequestWithContentMd5(): void
    {
        $headers = [
            'x-obs-date' => 'Tue, 15 Oct 2015 07:20:09 GMT',
            'Content-MD5' => 'I5pU0r4+sgO9Emgl1KMQUg==',
        ];

        $result = $this->signature->signRequest(
            'PUT',
            'bucket',
            'object.txt',
            [],
            $headers,
            ''
        );

        $this->assertArrayHasKey('Authorization', $result);
        $this->assertStringStartsWith('OBS testAccessKey:', $result['Authorization']);
    }

    public function testSignRequestWithMultipleSubResources(): void
    {
        $headers = [
            'Date' => 'Sat, 12 Oct 2015 08:12:38 GMT',
        ];

        $query = [
            'response-content-type' => 'text/plain',
            'versionId' => 'xxx',
        ];

        $result = $this->signature->signRequest(
            'GET',
            'bucket-test',
            'object-test',
            $query,
            $headers,
            ''
        );

        $this->assertArrayHasKey('Authorization', $result);
        $this->assertStringStartsWith('OBS testAccessKey:', $result['Authorization']);
    }

    public function testSignRequestWithoutBucket(): void
    {
        $headers = [
            'Date' => 'Sat, 12 Oct 2015 08:12:38 GMT',
        ];

        $result = $this->signature->signRequest(
            'GET',
            '',
            '',
            [],
            $headers,
            ''
        );

        $this->assertArrayHasKey('Authorization', $result);
        $this->assertStringStartsWith('OBS testAccessKey:', $result['Authorization']);
    }
}
