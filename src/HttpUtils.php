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

namespace Horde\Browser;

use Horde\Http\ServerRequest;
use Horde\Http\Stream;
use Horde\Http\StreamFactory;
use Horde\Http\UploadedFile;
use Horde\Http\Uri;
use Horde\Util\ArrayUtils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * HTTP utilities for file uploads, headers, and request information.
 *
 * This class provides utilities for:
 * - File upload validation
 * - Download headers
 * - HTTP protocol detection
 * - Client IP detection
 * - SSL connection detection
 *
 * @category Horde
 * @package  Browser
 */
class HttpUtils
{
    public function __construct(
        private readonly ServerRequestInterface $request
    ) {}

    /**
     * Create HttpUtils from PHP superglobals.
     *
     * @return self
     */
    public static function fromGlobals(): self
    {
        // Build URI from $_SERVER
        $uri = self::buildUriFromGlobals();

        // Get request method
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Get headers from $_SERVER
        $headers = self::extractHeadersFromGlobals();

        // Get protocol version
        $protocol = '1.1';
        if (isset($_SERVER['SERVER_PROTOCOL'])) {
            if (preg_match('/^HTTP\/(\d+\.\d+)$/', $_SERVER['SERVER_PROTOCOL'], $matches)) {
                $protocol = $matches[1];
            }
        }

        // Create request body stream
        $body = new Stream(fopen('php://input', 'r'));

        // Create ServerRequest
        $request = new ServerRequest(
            $method,
            $uri,
            $headers,
            $body,
            $protocol,
            $_SERVER
        );

        // Add parsed body
        if ($method === 'POST' && !empty($_POST)) {
            $request = $request->withParsedBody($_POST);
        }

        // Add query params
        $request = $request->withQueryParams($_GET);

        // Add cookie params
        $request = $request->withCookieParams($_COOKIE);

        // Add uploaded files
        $uploadedFiles = self::buildUploadedFilesFromGlobals();
        $request = $request->withUploadedFiles($uploadedFiles);

        return new self($request);
    }

    /**
     * Build URI from $_SERVER superglobal.
     *
     * @return Uri
     */
    private static function buildUriFromGlobals(): Uri
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $port = isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : null;
        $path = $_SERVER['REQUEST_URI'] ?? '/';

        // Remove query string from path
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }

        $query = $_SERVER['QUERY_STRING'] ?? '';

        $uriString = $scheme . '://' . $host;

        // Add port if non-standard
        if ($port !== null && !(($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443))) {
            $uriString .= ':' . $port;
        }

        $uriString .= $path;

        if ($query !== '') {
            $uriString .= '?' . $query;
        }

        return new Uri($uriString);
    }

    /**
     * Extract HTTP headers from $_SERVER superglobal.
     *
     * @return array<string, string>
     */
    private static function extractHeadersFromGlobals(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                // Convert HTTP_HEADER_NAME to Header-Name
                $headerName = str_replace('_', '-', substr($key, 5));
                $headerName = ucwords(strtolower($headerName), '-');
                $headers[$headerName] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headerName = str_replace('_', '-', $key);
                $headerName = ucwords(strtolower($headerName), '-');
                $headers[$headerName] = $value;
            }
        }

        return $headers;
    }

    /**
     * Build UploadedFile objects from $_FILES superglobal.
     *
     * @return array
     */
    private static function buildUploadedFilesFromGlobals(): array
    {
        $uploadedFiles = [];

        foreach ($_FILES as $key => $file) {
            $uploadedFiles[$key] = self::buildUploadedFileTree($file);
        }

        return $uploadedFiles;
    }

    /**
     * Recursively build UploadedFile tree from $_FILES structure.
     *
     * @param array $fileInfo File info from $_FILES
     * @return UploadedFile|array
     */
    private static function buildUploadedFileTree(array $fileInfo): UploadedFile|array
    {
        $streamFactory = new StreamFactory();

        // Simple file: array with 'tmp_name', 'error', etc.
        if (isset($fileInfo['tmp_name']) && !is_array($fileInfo['tmp_name'])) {
            $stream = $streamFactory->createStream('');

            if ($fileInfo['error'] === UPLOAD_ERR_OK && !empty($fileInfo['tmp_name'])) {
                $stream = $streamFactory->createStreamFromFile($fileInfo['tmp_name'], 'r');
            }

            return new UploadedFile(
                $stream,
                $streamFactory,
                $fileInfo['name'] ?? '',
                $fileInfo['type'] ?? '',
                $fileInfo['error'] ?? UPLOAD_ERR_NO_FILE,
                $fileInfo['size'] ?? 0
            );
        }

        // Nested structure: array of arrays
        if (is_array($fileInfo['tmp_name'])) {
            $files = [];
            foreach (array_keys($fileInfo['tmp_name']) as $key) {
                $files[$key] = self::buildUploadedFileTree([
                    'tmp_name' => $fileInfo['tmp_name'][$key],
                    'size' => $fileInfo['size'][$key],
                    'error' => $fileInfo['error'][$key],
                    'name' => $fileInfo['name'][$key],
                    'type' => $fileInfo['type'][$key],
                ]);
            }
            return $files;
        }

        // Fallback for malformed data
        $stream = $streamFactory->createStream('');
        return new UploadedFile(
            $stream,
            $streamFactory,
            '',
            '',
            UPLOAD_ERR_NO_FILE,
            0
        );
    }

    /**
     * Check if PHP allows file uploads and return maximum upload size.
     *
     * @return int Maximum upload size in bytes, or 0 if uploads disabled
     */
    public static function allowFileUploads(): int
    {
        if (!ini_get('file_uploads')
            || (($dir = ini_get('upload_tmp_dir'))
             && !is_writable($dir))) {
            return 0;
        }

        $filesize = self::parseIniSize(ini_get('upload_max_filesize'));
        $postsize = self::parseIniSize(ini_get('post_max_size'));

        return min($filesize, $postsize);
    }

    /**
     * Parse PHP ini size value (e.g., "8M" -> 8388608).
     *
     * @param string $size Size string
     * @return int Size in bytes
     */
    private static function parseIniSize(string $size): int
    {
        $unit = strtolower(substr($size, -1, 1));
        $value = floatval($size);

        return match ($unit) {
            'g' => intval($value * 1024 * 1024 * 1024),
            'm' => intval($value * 1024 * 1024),
            'k' => intval($value * 1024),
            default => intval($value),
        };
    }

    /**
     * Check if a file was uploaded successfully.
     *
     * Validates file upload and throws exception with detailed error messages
     * for various upload failure conditions.
     *
     * Supports both simple field names and nested array notation:
     * - Simple: "photo" -> $uploadedFiles['photo']
     * - Nested: "object[photo][new]" -> $uploadedFiles['object']['photo']['new']
     *
     * @param string $field Form field name (supports array notation)
     * @param string $name File description for error messages
     *
     * @return void
     *
     * @throws Exception If upload failed or file not uploaded
     */
    public function wasFileUploaded(string $field, string $name = 'file'): void
    {
        $uploadedFiles = $this->request->getUploadedFiles();

        // Handle both simple field names and nested array notation
        $hasNestedArrayNotation = strpos($field, '[') !== false;

        if ($hasNestedArrayNotation) {
            // Parse nested array notation (e.g., "object[photo][new]")
            ArrayUtils::getArrayParts($field, $base, $keys);

            // Navigate nested structure
            $keys_path = array_merge([$base], $keys);
            $uploadedFile = ArrayUtils::getElement($uploadedFiles, $keys_path);

            if ($uploadedFile === false || $uploadedFile === null) {
                throw new Exception(
                    "No $name was uploaded",
                    UPLOAD_ERR_NO_FILE
                );
            }
        } else {
            // Simple field name
            if (!isset($uploadedFiles[$field])) {
                throw new Exception(
                    "No $name was uploaded",
                    UPLOAD_ERR_NO_FILE
                );
            }

            $uploadedFile = $uploadedFiles[$field];
        }

        // Validate it's an UploadedFile object
        if (!($uploadedFile instanceof UploadedFileInterface)) {
            throw new Exception(
                "Invalid uploaded file structure for $name",
                UPLOAD_ERR_NO_FILE
            );
        }

        // Check for upload errors
        $error = $uploadedFile->getError();

        switch ($error) {
            case UPLOAD_ERR_NO_FILE:
                throw new Exception(
                    "No $name was uploaded",
                    UPLOAD_ERR_NO_FILE
                );

            case UPLOAD_ERR_OK:
                // Check if file is empty
                if ($uploadedFile->getSize() === 0) {
                    throw new Exception(
                        "The uploaded $name appears to be empty",
                        UPLOAD_ERR_NO_FILE
                    );
                }
                // SUCCESS
                break;

            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $uploadSize = self::allowFileUploads();
                throw new Exception(
                    "The $name was larger than the maximum allowed size ($uploadSize bytes)",
                    $error
                );

            case UPLOAD_ERR_PARTIAL:
                throw new Exception(
                    "The $name was only partially uploaded",
                    $error
                );

            case UPLOAD_ERR_NO_TMP_DIR:
                throw new Exception(
                    "The temporary folder used to store the upload data is missing",
                    $error
                );

            case UPLOAD_ERR_CANT_WRITE:
            case UPLOAD_ERR_EXTENSION:
                throw new Exception(
                    "Can't write the uploaded data to the server",
                    $error
                );

            default:
                throw new Exception(
                    "Unknown upload error for $name",
                    $error
                );
        }
    }

    /**
     * Send download headers for file downloads.
     *
     * @param string $filename Filename for download
     * @param string|null $contentType MIME content type
     * @param bool $inline Display inline or as attachment
     * @param int|null $contentLength Content length in bytes
     *
     * @return void
     */
    public function downloadHeaders(
        string $filename,
        ?string $contentType = null,
        bool $inline = false,
        ?int $contentLength = null
    ): void {
        // Sanitize filename
        $filename = str_replace(["\r", "\n"], '', $filename);

        // Set content type
        if ($contentType === null) {
            $contentType = 'application/octet-stream';
        }
        header('Content-Type: ' . $contentType);

        // Set content disposition
        $disposition = $inline ? 'inline' : 'attachment';
        $encodedFilename = rawurlencode($filename);
        header("Content-Disposition: $disposition; filename*=UTF-8''$encodedFilename");

        // Set content length if provided
        if ($contentLength !== null) {
            header('Content-Length: ' . $contentLength);
        }

        // Prevent caching
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    }

    /**
     * Get HTTP protocol version from request.
     *
     * @return string|null Protocol version (e.g., "1.1", "2.0") or null if unknown
     */
    public function getHTTPProtocol(): ?string
    {
        return $this->request->getProtocolVersion();
    }

    /**
     * Get client IP address from request.
     *
     * Checks X-Forwarded-For header first (for proxied requests),
     * falls back to direct connection IP.
     *
     * @return string Client IP address
     */
    public function getIPAddress(): string
    {
        // Check for X-Forwarded-For header (proxy)
        $forwardedFor = $this->request->getHeaderLine('X-Forwarded-For');
        if ($forwardedFor !== '') {
            // X-Forwarded-For can contain multiple IPs, take the first
            $ips = explode(',', $forwardedFor);
            return trim($ips[0]);
        }

        // Fall back to server params
        $serverParams = $this->request->getServerParams();
        return $serverParams['REMOTE_ADDR'] ?? '';
    }

    /**
     * Check if request is using SSL/TLS connection.
     *
     * @return bool True if using SSL/TLS
     */
    public function usingSSLConnection(): bool
    {
        $uri = $this->request->getUri();
        $scheme = $uri->getScheme();

        if ($scheme === 'https') {
            return true;
        }

        // Additional check for SSL protocol environment variable
        $serverParams = $this->request->getServerParams();
        if (!empty($serverParams['SSL_PROTOCOL_VERSION'])) {
            return true;
        }

        return false;
    }
}
