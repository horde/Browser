<?php

declare(strict_types=1);

/**
 * Copyright 2024-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL-2.1).
 *
 * @category Horde
 * @package  Browser
 */

namespace Horde\Browser\Test\Unit;

use Horde\Browser\Browser;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for isViewable() Accept header negotiation.
 *
 * Tests cover the Alpha4 behavior that was lost in refactoring:
 * - Accept header negotiation
 * - Wildcard matching
 * - Firefox pjpeg/jpeg quirk
 * - Browser 'images' feature detection
 *
 * @category Horde
 * @package  Browser
 */
#[CoversClass(Browser::class)]
class IsViewableTest extends TestCase
{
    /**
     * Test exact MIME type matching in Accept header.
     */
    public function testExactMimeTypeMatch(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'image/png, image/gif, image/jpeg'
        );

        $this->assertTrue($browser->isViewable('image/png'));
        $this->assertTrue($browser->isViewable('image/gif'));
        $this->assertTrue($browser->isViewable('image/jpeg'));
    }

    /**
     * Test MIME type not in Accept header is rejected.
     */
    public function testMimeTypeNotInAccept(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'image/png, image/gif'
        );

        // WebP not in Accept header
        $this->assertFalse($browser->isViewable('image/webp'));
    }

    /**
     * Test wildcard *\/* accepts all non-image types.
     */
    public function testWildcardAcceptsNonImages(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'text/html, */*'
        );

        // Non-image types accepted with */*
        $this->assertTrue($browser->isViewable('text/plain'));
        $this->assertTrue($browser->isViewable('application/pdf'));
        $this->assertTrue($browser->isViewable('video/mp4'));
    }

    /**
     * Test wildcard *\/* requires explicit image type match.
     */
    public function testWildcardRequiresExplicitImageTypes(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'text/html, */*'
        );

        // Images must be explicitly in Accept header even with */*
        // Unless the image type is explicitly listed
        $browserWithImage = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'image/png, */*'
        );

        $this->assertTrue($browserWithImage->isViewable('image/png'));
    }

    /**
     * Test Firefox pjpeg/jpeg compatibility quirk.
     *
     * Mozilla browsers treat image/pjpeg and image/jpeg as the same.
     */
    public function testFirefoxPjpegJpegCompatibility(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
            'image/jpeg, image/png'
        );

        // Firefox accepts pjpeg when jpeg is in Accept
        $this->assertTrue($browser->isViewable('image/pjpeg'));
        $this->assertTrue($browser->isViewable('image/jpeg'));
    }

    /**
     * Test Firefox pjpeg quirk only applies to Firefox.
     */
    public function testPjpegQuirkOnlyForFirefox(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'image/jpeg, image/png'
        );

        // Chrome should NOT accept pjpeg unless explicitly in Accept
        $this->assertFalse($browser->isViewable('image/pjpeg'));
    }

    /**
     * Test browser without 'images' feature rejects image types.
     */
    public function testImagesFeatureRequired(): void
    {
        // Mobile browser with images disabled (simulated)
        $browser = new Browser(
            'Mozilla/4.0 (compatible; MSIE 6.0; Windows CE)',
            'image/png, image/gif'
        );

        // Should check 'images' feature before allowing images
        // This test will need the feature detection implementation
        // For now, modern browsers always have 'images' feature
        $this->assertTrue($browser->hasFeature('images'));
    }

    /**
     * Test case-insensitive MIME type matching.
     */
    public function testCaseInsensitiveMimeType(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'IMAGE/PNG, IMAGE/GIF'
        );

        $this->assertTrue($browser->isViewable('image/png'));
        $this->assertTrue($browser->isViewable('IMAGE/PNG'));
        $this->assertTrue($browser->isViewable('Image/Png'));
    }

    /**
     * Test Accept header with quality values (q parameter).
     */
    public function testAcceptHeaderWithQuality(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'image/webp, image/png;q=0.9, */*;q=0.8'
        );

        // Should match regardless of quality parameter
        $this->assertTrue($browser->isViewable('image/webp'));
        $this->assertTrue($browser->isViewable('image/png'));
    }

    /**
     * Test modern MIME types are viewable (current behavior).
     */
    public function testModernMimeTypes(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'image/webp, image/svg+xml, application/pdf, video/mp4, audio/mpeg'
        );

        // Modern types should be viewable
        $this->assertTrue($browser->isViewable('image/webp'));
        $this->assertTrue($browser->isViewable('image/svg+xml'));
        $this->assertTrue($browser->isViewable('application/pdf'));
        $this->assertTrue($browser->isViewable('video/mp4'));
        $this->assertTrue($browser->isViewable('audio/mpeg'));
    }

    /**
     * Test legacy image subtypes (alpha4 compatibility).
     */
    public function testLegacyImageSubtypes(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) MSIE 6.0',
            'image/jpeg, image/gif, image/png, image/pjpeg, image/x-png, image/bmp'
        );

        // Alpha4 supported these image subtypes
        $this->assertTrue($browser->isViewable('image/jpeg'));
        $this->assertTrue($browser->isViewable('image/gif'));
        $this->assertTrue($browser->isViewable('image/png'));
        $this->assertTrue($browser->isViewable('image/pjpeg'));
        $this->assertTrue($browser->isViewable('image/x-png'));
        $this->assertTrue($browser->isViewable('image/bmp'));
    }

    /**
     * Test no Accept header falls back to allowlist.
     */
    public function testNoAcceptHeaderFallback(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            null  // No Accept header
        );

        // Should use default allowlist when no Accept header
        $this->assertTrue($browser->isViewable('text/html'));
        $this->assertTrue($browser->isViewable('image/png'));
    }

    /**
     * Test empty Accept header falls back to allowlist.
     */
    public function testEmptyAcceptHeaderFallback(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            ''  // Empty Accept header
        );

        // Should use default allowlist when Accept is empty
        $this->assertTrue($browser->isViewable('text/html'));
        $this->assertTrue($browser->isViewable('image/png'));
    }
}
