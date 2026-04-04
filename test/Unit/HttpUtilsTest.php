<?php

declare(strict_types=1);

/**
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL-2.1).
 *
 * @category Horde
 * @package  Browser
 */

namespace Horde\Browser\Test\Unit;

use Horde\Browser\Exception;
use Horde\Browser\HttpUtils;
use Horde\Http\ServerRequest;
use Horde\Http\StreamFactory;
use Horde\Http\UploadedFile;
use Horde\Http\Uri;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for HttpUtils class.
 *
 * @category Horde
 * @package  Browser
 */
#[CoversClass(HttpUtils::class)]
class HttpUtilsTest extends TestCase
{
    private StreamFactory $streamFactory;

    protected function setUp(): void
    {
        $this->streamFactory = new StreamFactory();
    }

    protected function tearDown(): void
    {
        // Clean up superglobals
        $_SERVER = [];
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_FILES = [];
    }

    /**
     * Test allowFileUploads returns size when enabled
     */
    public function testAllowFileUploadsReturnsSize(): void
    {
        $maxSize = HttpUtils::allowFileUploads();

        // Should return a positive integer if file uploads are enabled
        $this->assertIsInt($maxSize);
        $this->assertGreaterThanOrEqual(0, $maxSize);
    }

    /**
     * Test wasFileUploaded with simple field name
     */
    public function testWasFileUploadedWithSimpleField(): void
    {
        $stream = $this->streamFactory->createStream('test content');

        $uploadedFile = new UploadedFile(
            $stream,
            $this->streamFactory,
            'test.txt',
            'text/plain',
            UPLOAD_ERR_OK,
            12
        );

        $request = $this->createRequestWithUploadedFiles([
            'photo' => $uploadedFile,
        ]);

        $utils = new HttpUtils($request);
        $utils->wasFileUploaded('photo', 'test file');

        // If we get here without exception, test passed
        $this->assertTrue(true);
    }

    /**
     * Test wasFileUploaded with nested array notation
     */
    public function testWasFileUploadedWithNestedNotation(): void
    {
        $stream = $this->streamFactory->createStream('test content');

        $uploadedFile = new UploadedFile(
            $stream,
            $this->streamFactory,
            'test.png',
            'image/png',
            UPLOAD_ERR_OK,
            12
        );

        $request = $this->createRequestWithUploadedFiles([
            'object' => [
                'photo' => [
                    'new' => $uploadedFile,
                ],
            ],
        ]);

        $utils = new HttpUtils($request);
        $utils->wasFileUploaded('object[photo][new]', 'photo');

        $this->assertTrue(true);
    }

    /**
     * Test wasFileUploaded throws exception for missing file
     */
    public function testWasFileUploadedThrowsExceptionForMissingFile(): void
    {
        $request = $this->createRequestWithUploadedFiles([]);

        $utils = new HttpUtils($request);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(UPLOAD_ERR_NO_FILE);

        $utils->wasFileUploaded('photo');
    }

    /**
     * Test wasFileUploaded throws exception for NO_FILE error
     */
    public function testWasFileUploadedThrowsExceptionForNoFileError(): void
    {
        $stream = $this->streamFactory->createStream('');

        $uploadedFile = new UploadedFile(
            $stream,
            $this->streamFactory,
            '',
            '',
            UPLOAD_ERR_NO_FILE,
            0
        );

        $request = $this->createRequestWithUploadedFiles([
            'photo' => $uploadedFile,
        ]);

        $utils = new HttpUtils($request);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(UPLOAD_ERR_NO_FILE);

        $utils->wasFileUploaded('photo');
    }

    /**
     * Test wasFileUploaded throws exception for empty file
     */
    public function testWasFileUploadedThrowsExceptionForEmptyFile(): void
    {
        $stream = $this->streamFactory->createStream('');

        $uploadedFile = new UploadedFile(
            $stream,
            $this->streamFactory,
            'empty.txt',
            'text/plain',
            UPLOAD_ERR_OK,
            0 // Zero size
        );

        $request = $this->createRequestWithUploadedFiles([
            'photo' => $uploadedFile,
        ]);

        $utils = new HttpUtils($request);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(UPLOAD_ERR_NO_FILE);

        $utils->wasFileUploaded('photo');
    }

    /**
     * Test wasFileUploaded throws exception for file too large
     */
    public function testWasFileUploadedThrowsExceptionForFileTooLarge(): void
    {
        $stream = $this->streamFactory->createStream('');

        $uploadedFile = new UploadedFile(
            $stream,
            $this->streamFactory,
            'huge.bin',
            'application/octet-stream',
            UPLOAD_ERR_INI_SIZE,
            0
        );

        $request = $this->createRequestWithUploadedFiles([
            'photo' => $uploadedFile,
        ]);

        $utils = new HttpUtils($request);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(UPLOAD_ERR_INI_SIZE);

        $utils->wasFileUploaded('photo');
    }

    /**
     * Test wasFileUploaded throws exception for partial upload
     */
    public function testWasFileUploadedThrowsExceptionForPartialUpload(): void
    {
        $stream = $this->streamFactory->createStream('partial');

        $uploadedFile = new UploadedFile(
            $stream,
            $this->streamFactory,
            'partial.txt',
            'text/plain',
            UPLOAD_ERR_PARTIAL,
            7
        );

        $request = $this->createRequestWithUploadedFiles([
            'photo' => $uploadedFile,
        ]);

        $utils = new HttpUtils($request);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(UPLOAD_ERR_PARTIAL);

        $utils->wasFileUploaded('photo');
    }

    /**
     * Test wasFileUploaded with missing nested field
     */
    public function testWasFileUploadedThrowsExceptionForMissingNestedField(): void
    {
        $stream = $this->streamFactory->createStream('');

        $uploadedFile = new UploadedFile(
            $stream,
            $this->streamFactory,
            '',
            '',
            UPLOAD_ERR_NO_FILE,
            0
        );

        $request = $this->createRequestWithUploadedFiles([
            'object' => [
                'logo' => [
                    'new' => $uploadedFile,
                ],
            ],
        ]);

        $utils = new HttpUtils($request);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(UPLOAD_ERR_NO_FILE);

        $utils->wasFileUploaded('object[photo][new]'); // Different field
    }

    /**
     * Test getHTTPProtocol returns protocol version
     */
    public function testGetHTTPProtocolReturnsVersion(): void
    {
        $request = new ServerRequest(
            'GET',
            new Uri('http://localhost'),
            [],
            null,
            '1.1',
            []
        );

        $utils = new HttpUtils($request);

        $this->assertSame('1.1', $utils->getHTTPProtocol());
    }

    /**
     * Test getHTTPProtocol with HTTP/2
     */
    public function testGetHTTPProtocolWithHTTP2(): void
    {
        $request = new ServerRequest(
            'GET',
            new Uri('https://localhost'),
            [],
            null,
            '2.0',
            []
        );

        $utils = new HttpUtils($request);

        $this->assertSame('2.0', $utils->getHTTPProtocol());
    }

    /**
     * Test getIPAddress returns remote address
     */
    public function testGetIPAddressReturnsRemoteAddr(): void
    {
        $request = new ServerRequest(
            'GET',
            new Uri('http://localhost'),
            [],
            null,
            '1.1',
            ['REMOTE_ADDR' => '192.168.1.100']
        );

        $utils = new HttpUtils($request);

        $this->assertSame('192.168.1.100', $utils->getIPAddress());
    }

    /**
     * Test getIPAddress with X-Forwarded-For header
     */
    public function testGetIPAddressWithForwardedHeader(): void
    {
        $request = new ServerRequest(
            'GET',
            new Uri('http://localhost'),
            ['X-Forwarded-For' => '10.0.0.1, 192.168.1.100'],
            null,
            '1.1',
            ['REMOTE_ADDR' => '172.16.0.1']
        );

        $utils = new HttpUtils($request);

        // Should return first IP from X-Forwarded-For
        $this->assertSame('10.0.0.1', $utils->getIPAddress());
    }

    /**
     * Test getIPAddress returns empty string when no IP available
     */
    public function testGetIPAddressReturnsEmptyWhenMissing(): void
    {
        $request = new ServerRequest(
            'GET',
            new Uri('http://localhost'),
            [],
            null,
            '1.1',
            []
        );

        $utils = new HttpUtils($request);

        $this->assertSame('', $utils->getIPAddress());
    }

    /**
     * Test usingSSLConnection returns true for HTTPS
     */
    public function testUsingSSLConnectionReturnsTrueForHTTPS(): void
    {
        $request = new ServerRequest(
            'GET',
            new Uri('https://localhost'),
            [],
            null,
            '1.1',
            []
        );

        $utils = new HttpUtils($request);

        $this->assertTrue($utils->usingSSLConnection());
    }

    /**
     * Test usingSSLConnection returns false for HTTP
     */
    public function testUsingSSLConnectionReturnsFalseForHTTP(): void
    {
        $request = new ServerRequest(
            'GET',
            new Uri('http://localhost'),
            [],
            null,
            '1.1',
            []
        );

        $utils = new HttpUtils($request);

        $this->assertFalse($utils->usingSSLConnection());
    }

    /**
     * Test usingSSLConnection detects SSL_PROTOCOL_VERSION
     */
    public function testUsingSSLConnectionDetectsSSLProtocolEnv(): void
    {
        $request = new ServerRequest(
            'GET',
            new Uri('http://localhost'), // HTTP URI but SSL env var
            [],
            null,
            '1.1',
            ['SSL_PROTOCOL_VERSION' => 'TLSv1.2']
        );

        $utils = new HttpUtils($request);

        $this->assertTrue($utils->usingSSLConnection());
    }

    /**
     * Test downloadHeaders sets correct headers
     */
    public function testDownloadHeadersSetsCorrectHeaders(): void
    {
        $request = new ServerRequest(
            'GET',
            new Uri('http://localhost'),
            [],
            null,
            '1.1',
            []
        );

        $utils = new HttpUtils($request);

        // Capture headers
        ob_start();
        $utils->downloadHeaders('test.pdf', 'application/pdf', false, 1024);
        ob_end_clean();

        // Note: We can't easily test header() calls in unit tests without output buffering
        // This test mainly ensures no exceptions are thrown
        $this->assertTrue(true);
    }

    /**
     * Test fromGlobals creates HttpUtils from superglobals
     */
    public function testFromGlobalsCreatesFromSuperglobals(): void
    {
        // Set up superglobals
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/test',
            'QUERY_STRING' => 'foo=bar',
            'REMOTE_ADDR' => '127.0.0.1',
        ];
        $_GET = ['foo' => 'bar'];

        $utils = HttpUtils::fromGlobals();

        $this->assertInstanceOf(HttpUtils::class, $utils);
        $this->assertSame('1.1', $utils->getHTTPProtocol());
        $this->assertSame('127.0.0.1', $utils->getIPAddress());
    }

    /**
     * Test fromGlobals with HTTPS
     */
    public function testFromGlobalsWithHTTPS(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/test',
            'HTTPS' => 'on',
            'REMOTE_ADDR' => '127.0.0.1',
        ];

        $utils = HttpUtils::fromGlobals();

        $this->assertTrue($utils->usingSSLConnection());
    }

    /**
     * Test fromGlobals with uploaded files
     */
    public function testFromGlobalsWithUploadedFiles(): void
    {
        // Create a real temporary file for testing
        $tmpFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tmpFile, 'test content');

        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/upload',
            'REMOTE_ADDR' => '127.0.0.1',
        ];

        $_FILES = [
            'photo' => [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'tmp_name' => $tmpFile,
                'error' => UPLOAD_ERR_OK,
                'size' => 12,
            ],
        ];

        $utils = HttpUtils::fromGlobals();

        // Should not throw exception
        $utils->wasFileUploaded('photo');

        $this->assertTrue(true);

        // Cleanup
        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }
    }

    /**
     * Test fromGlobals with nested uploaded files
     */
    public function testFromGlobalsWithNestedUploadedFiles(): void
    {
        // Create temporary files
        $tmpFile1 = tempnam(sys_get_temp_dir(), 'test');
        $tmpFile2 = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tmpFile1, 'photo content');
        file_put_contents($tmpFile2, '');

        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/upload',
            'REMOTE_ADDR' => '127.0.0.1',
        ];

        $_FILES = [
            'object' => [
                'name' => [
                    'photo' => [
                        'new' => 'contact.jpg',
                    ],
                    'logo' => [
                        'new' => '',
                    ],
                ],
                'type' => [
                    'photo' => [
                        'new' => 'image/jpeg',
                    ],
                    'logo' => [
                        'new' => '',
                    ],
                ],
                'tmp_name' => [
                    'photo' => [
                        'new' => $tmpFile1,
                    ],
                    'logo' => [
                        'new' => $tmpFile2,
                    ],
                ],
                'error' => [
                    'photo' => [
                        'new' => UPLOAD_ERR_OK,
                    ],
                    'logo' => [
                        'new' => UPLOAD_ERR_NO_FILE,
                    ],
                ],
                'size' => [
                    'photo' => [
                        'new' => 13,
                    ],
                    'logo' => [
                        'new' => 0,
                    ],
                ],
            ],
        ];

        $utils = HttpUtils::fromGlobals();

        // Photo should succeed
        $utils->wasFileUploaded('object[photo][new]', 'photo');
        $this->assertTrue(true);

        // Logo should fail
        $this->expectException(Exception::class);
        $this->expectExceptionCode(UPLOAD_ERR_NO_FILE);
        $utils->wasFileUploaded('object[logo][new]', 'logo');

        // Cleanup
        if (file_exists($tmpFile1)) {
            unlink($tmpFile1);
        }
        if (file_exists($tmpFile2)) {
            unlink($tmpFile2);
        }
    }

    /**
     * Test Turba contact photo upload scenario
     */
    public function testTurbaContactPhotoScenario(): void
    {
        $photoStream = $this->streamFactory->createStream('photo data');
        $logoStream = $this->streamFactory->createStream('');

        $photoFile = new UploadedFile(
            $photoStream,
            $this->streamFactory,
            'contact-photo.jpg',
            'image/jpeg',
            UPLOAD_ERR_OK,
            10
        );

        $logoFile = new UploadedFile(
            $logoStream,
            $this->streamFactory,
            '',
            '',
            UPLOAD_ERR_NO_FILE,
            0
        );

        $request = $this->createRequestWithUploadedFiles([
            'object' => [
                'photo' => [
                    'new' => $photoFile,
                ],
                'logo' => [
                    'new' => $logoFile,
                ],
            ],
        ]);

        $utils = new HttpUtils($request);

        // Photo should succeed
        $utils->wasFileUploaded('object[photo][new]', 'photo');
        $this->assertTrue(true);

        // Logo should fail
        $this->expectException(Exception::class);
        $this->expectExceptionCode(UPLOAD_ERR_NO_FILE);
        $utils->wasFileUploaded('object[logo][new]', 'logo');
    }

    /**
     * Helper to create ServerRequest with uploaded files
     */
    private function createRequestWithUploadedFiles(array $uploadedFiles): ServerRequest
    {
        $request = new ServerRequest(
            'POST',
            new Uri('http://localhost'),
            [],
            null,
            '1.1',
            []
        );

        return $request->withUploadedFiles($uploadedFiles);
    }
}
