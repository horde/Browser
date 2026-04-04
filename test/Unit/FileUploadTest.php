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

use Horde_Browser;
use Horde_Browser_Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for file upload handling in Horde_Browser.
 *
 * @category Horde
 * @package  Browser
 */
#[CoversClass(Horde_Browser::class)]
class FileUploadTest extends TestCase
{
    private Horde_Browser $browser;

    protected function setUp(): void
    {
        $this->browser = $GLOBALS['browser'] = new Horde_Browser();
    }

    protected function tearDown(): void
    {
        // Clean up $_FILES superglobal
        $_FILES = [];
    }

    /**
     * Test simple field name (backwards compatibility)
     */
    public function testWasFileUploadedWithSimpleFieldName(): void
    {
        // Simulate a simple file upload: <input type="file" name="photo">
        $_FILES['photo'] = [
            'name' => 'test.png',
            'type' => 'image/png',
            'tmp_name' => '/tmp/phpXXXXXX',
            'error' => UPLOAD_ERR_OK,
            'size' => 1024,
        ];

        // Should not throw exception
        $this->browser->wasFileUploaded('photo');
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    /**
     * Test nested array notation (new functionality for Horde_Form)
     */
    public function testWasFileUploadedWithNestedArrayNotation(): void
    {
        // Simulate nested file upload: <input type="file" name="object[photo][new]">
        // PHP converts this to nested array structure in $_FILES
        $_FILES['object'] = [
            'name' => [
                'photo' => [
                    'new' => 'test.png',
                ],
            ],
            'type' => [
                'photo' => [
                    'new' => 'image/png',
                ],
            ],
            'tmp_name' => [
                'photo' => [
                    'new' => '/tmp/phpXXXXXX',
                ],
            ],
            'error' => [
                'photo' => [
                    'new' => UPLOAD_ERR_OK,
                ],
            ],
            'size' => [
                'photo' => [
                    'new' => 1024,
                ],
            ],
        ];

        // Should not throw exception with nested notation
        $this->browser->wasFileUploaded('object[photo][new]');
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    /**
     * Test multiple levels of nesting
     */
    public function testWasFileUploadedWithDeeplyNestedNotation(): void
    {
        // Simulate deeply nested: <input type="file" name="form[contact][data][photo]">
        $_FILES['form'] = [
            'name' => [
                'contact' => [
                    'data' => [
                        'photo' => 'test.png',
                    ],
                ],
            ],
            'type' => [
                'contact' => [
                    'data' => [
                        'photo' => 'image/png',
                    ],
                ],
            ],
            'tmp_name' => [
                'contact' => [
                    'data' => [
                        'photo' => '/tmp/phpXXXXXX',
                    ],
                ],
            ],
            'error' => [
                'contact' => [
                    'data' => [
                        'photo' => UPLOAD_ERR_OK,
                    ],
                ],
            ],
            'size' => [
                'contact' => [
                    'data' => [
                        'photo' => 1024,
                    ],
                ],
            ],
        ];

        // Should handle deep nesting
        $this->browser->wasFileUploaded('form[contact][data][photo]');
        $this->assertTrue(true);
    }

    /**
     * Test exception for missing simple field
     */
    public function testWasFileUploadedThrowsExceptionForMissingSimpleField(): void
    {
        $_FILES = []; // No files uploaded

        $this->expectException(Horde_Browser_Exception::class);
        $this->expectExceptionCode(UPLOAD_ERR_NO_FILE);

        $this->browser->wasFileUploaded('photo');
    }

    /**
     * Test exception for missing nested field
     */
    public function testWasFileUploadedThrowsExceptionForMissingNestedField(): void
    {
        // Files array exists but the specific nested field doesn't exist at all
        $_FILES['object'] = [
            'name' => [
                'logo' => [
                    'new' => '',
                ],
            ],
            'error' => [
                'logo' => [
                    'new' => UPLOAD_ERR_NO_FILE,
                ],
            ],
            'tmp_name' => [
                'logo' => [
                    'new' => '',
                ],
            ],
            'type' => [
                'logo' => [
                    'new' => '',
                ],
            ],
            'size' => [
                'logo' => [
                    'new' => 0,
                ],
            ],
        ];

        // Trying to access object[photo][new] which doesn't exist in the structure
        // ArrayUtils::getElement will return null for non-existent nested keys
        $this->expectException(Horde_Browser_Exception::class);
        $this->expectExceptionCode(UPLOAD_ERR_NO_FILE);

        $this->browser->wasFileUploaded('object[photo][new]');
    }

    /**
     * Test UPLOAD_ERR_NO_FILE error handling with simple field
     */
    public function testWasFileUploadedHandlesNoFileErrorSimple(): void
    {
        $_FILES['photo'] = [
            'name' => '',
            'type' => '',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0,
        ];

        $this->expectException(Horde_Browser_Exception::class);
        $this->expectExceptionCode(UPLOAD_ERR_NO_FILE);

        $this->browser->wasFileUploaded('photo');
    }

    /**
     * Test UPLOAD_ERR_NO_FILE error handling with nested field
     */
    public function testWasFileUploadedHandlesNoFileErrorNested(): void
    {
        $_FILES['object'] = [
            'name' => [
                'photo' => [
                    'new' => '',
                ],
            ],
            'type' => [
                'photo' => [
                    'new' => '',
                ],
            ],
            'tmp_name' => [
                'photo' => [
                    'new' => '',
                ],
            ],
            'error' => [
                'photo' => [
                    'new' => UPLOAD_ERR_NO_FILE,
                ],
            ],
            'size' => [
                'photo' => [
                    'new' => 0,
                ],
            ],
        ];

        $this->expectException(Horde_Browser_Exception::class);
        $this->expectExceptionCode(UPLOAD_ERR_NO_FILE);

        $this->browser->wasFileUploaded('object[photo][new]');
    }

    /**
     * Test UPLOAD_ERR_INI_SIZE error handling
     */
    public function testWasFileUploadedHandlesFileTooLargeError(): void
    {
        $_FILES['photo'] = [
            'name' => 'huge.png',
            'type' => 'image/png',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_INI_SIZE,
            'size' => 0,
        ];

        $this->expectException(Horde_Browser_Exception::class);
        $this->expectExceptionCode(UPLOAD_ERR_INI_SIZE);

        $this->browser->wasFileUploaded('photo');
    }

    /**
     * Test UPLOAD_ERR_PARTIAL error handling with nested field
     */
    public function testWasFileUploadedHandlesPartialUploadErrorNested(): void
    {
        $_FILES['object'] = [
            'name' => [
                'photo' => [
                    'new' => 'test.png',
                ],
            ],
            'type' => [
                'photo' => [
                    'new' => 'image/png',
                ],
            ],
            'tmp_name' => [
                'photo' => [
                    'new' => '/tmp/phpXXXXXX',
                ],
            ],
            'error' => [
                'photo' => [
                    'new' => UPLOAD_ERR_PARTIAL,
                ],
            ],
            'size' => [
                'photo' => [
                    'new' => 512,
                ],
            ],
        ];

        $this->expectException(Horde_Browser_Exception::class);
        $this->expectExceptionCode(UPLOAD_ERR_PARTIAL);

        $this->browser->wasFileUploaded('object[photo][new]');
    }

    /**
     * Note: Testing empty file detection (filesize check) requires actual uploaded files
     * which cannot be reliably mocked in unit tests since is_uploaded_file() checks
     * PHP's internal upload mechanism. This is better tested via integration tests.
     */

    /**
     * Test real-world Turba contact photo scenario
     */
    public function testTurbaContactPhotoUploadScenario(): void
    {
        // This simulates the exact scenario from Turba's contact edit form
        // where photo field is rendered as: <input type="file" name="object[photo][new]">
        $_FILES['object'] = [
            'name' => [
                'photo' => [
                    'new' => 'contact-photo.jpg',
                ],
                'logo' => [
                    'new' => '', // Logo not uploaded
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
                    'new' => '/tmp/php1a2b3c',
                ],
                'logo' => [
                    'new' => '',
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
                    'new' => 24707, // Actual size from user's test
                ],
                'logo' => [
                    'new' => 0,
                ],
            ],
        ];

        // Photo upload should succeed
        $this->browser->wasFileUploaded('object[photo][new]');
        $this->assertTrue(true);

        // Logo upload should fail (no file uploaded)
        $this->expectException(Horde_Browser_Exception::class);
        $this->expectExceptionCode(UPLOAD_ERR_NO_FILE);
        $this->browser->wasFileUploaded('object[logo][new]');
    }
}
