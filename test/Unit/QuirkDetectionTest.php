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

/**
 * Tests for browser quirk detection.
 *
 * Tests cover the Alpha4 behavior that was lost in refactoring:
 * - IE version-specific quirks
 * - SSL/download quirks
 * - Rendering quirks
 * - Opera quirks
 * - Mobile quirks
 *
 * @category Horde
 * @package  Browser
 */
#[CoversClass(Browser::class)]
class QuirkDetectionTest extends TestCase
{
    /**
     * Test IE SSL download cache quirk (all IE versions).
     */
    public function testIESSLCacheQuirk(): void
    {
        $browser = new Browser(
            'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)'
        );

        // All IE versions need special SSL cache handling
        $this->assertTrue($browser->hasQuirk('cache_ssl_downloads'));
    }

    /**
     * Test IE cache same URL quirk (all IE versions).
     */
    public function testIECacheSameURLQuirk(): void
    {
        $browser = new Browser(
            'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)'
        );

        $this->assertTrue($browser->hasQuirk('cache_same_url'));
    }

    /**
     * Test IE disposition filename quirk (all IE versions).
     */
    public function testIEDispositionFilenameQuirk(): void
    {
        $browser = new Browser(
            'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)'
        );

        $this->assertTrue($browser->hasQuirk('break_disposition_filename'));
    }

    /**
     * Test IE 5.5 specific disposition header quirk.
     */
    public function testIE55DispositionHeaderQuirk(): void
    {
        $browser = new Browser(
            'Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 5.0)'
        );

        // Only IE 5.5 has this specific quirk
        $this->assertTrue($browser->hasQuirk('break_disposition_header'));
    }

    /**
     * Test IE 5.5 disposition quirk not on other versions.
     */
    public function testIE55QuirkNotOnOtherVersions(): void
    {
        $browser = new Browser(
            'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)'
        );

        // IE 6+ should NOT have the 5.5-specific quirk
        $this->assertFalse($browser->hasQuirk('break_disposition_header'));
    }

    /**
     * Test IE < 7 PNG transparency quirk (Windows only).
     */
    public function testIEPNGTransparencyQuirk(): void
    {
        $browser = new Browser(
            'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)'
        );

        // IE 6 on Windows has PNG transparency issues
        $this->assertTrue($browser->hasQuirk('png_transparency'));
    }

    /**
     * Test IE < 7 PNG quirk not on Mac.
     */
    public function testIEPNGQuirkNotOnMac(): void
    {
        $browser = new Browser(
            'Mozilla/4.0 (compatible; MSIE 5.0; Mac_PowerPC)'
        );

        // IE on Mac doesn't have PNG transparency quirk
        $this->assertFalse($browser->hasQuirk('png_transparency'));
    }

    /**
     * Test IE 7+ does not have PNG transparency quirk.
     */
    public function testIE7NoPNGQuirk(): void
    {
        $browser = new Browser(
            'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)'
        );

        // IE 7+ fixed PNG transparency
        $this->assertFalse($browser->hasQuirk('png_transparency'));
    }

    /**
     * Test IE 6 scrollbar quirk.
     */
    public function testIE6ScrollbarQuirk(): void
    {
        $browser = new Browser(
            'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)'
        );

        // IE 6 has scrollbar layout issues
        $this->assertTrue($browser->hasQuirk('scrollbar_in_way'));
    }

    /**
     * Test IE 5-6 broken multipart form quirk.
     */
    public function testIEBrokenMultipartFormQuirk(): void
    {
        $ie5 = new Browser(
            'Mozilla/4.0 (compatible; MSIE 5.0; Windows NT 5.0)'
        );
        $ie6 = new Browser(
            'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)'
        );

        $this->assertTrue($ie5->hasQuirk('broken_multipart_form'));
        $this->assertTrue($ie6->hasQuirk('broken_multipart_form'));
    }

    /**
     * Test IE 5-6 windowed controls quirk.
     */
    public function testIEWindowedControlsQuirk(): void
    {
        $ie5 = new Browser(
            'Mozilla/4.0 (compatible; MSIE 5.0; Windows NT 5.0)'
        );
        $ie6 = new Browser(
            'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)'
        );

        // IE 5-6 have ActiveX windowed control issues
        $this->assertTrue($ie5->hasQuirk('windowed_controls'));
        $this->assertTrue($ie6->hasQuirk('windowed_controls'));
    }

    /**
     * Test IE 7+ does not have windowed controls quirk.
     */
    public function testIE7NoWindowedControlsQuirk(): void
    {
        $browser = new Browser(
            'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)'
        );

        $this->assertFalse($browser->hasQuirk('windowed_controls'));
    }

    /**
     * Test Opera no filename spaces quirk (all versions).
     */
    public function testOperaNoFilenameSpacesQuirk(): void
    {
        $browser = new Browser(
            'Opera/9.80 (Windows NT 6.1; U; en) Presto/2.10.229 Version/11.60'
        );

        // Opera can't handle spaces in filenames
        $this->assertTrue($browser->hasQuirk('no_filename_spaces'));
    }

    /**
     * Test IE also has no filename spaces quirk.
     */
    public function testIENoFilenameSpacesQuirk(): void
    {
        $browser = new Browser(
            'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)'
        );

        // IE also needs spaces replaced
        $this->assertTrue($browser->hasQuirk('no_filename_spaces'));
    }

    /**
     * Test Opera >= 7 double linebreak textarea quirk.
     */
    public function testOperaDoubleLinebreakQuirk(): void
    {
        $opera7 = new Browser(
            'Opera/7.0 (Windows NT 5.1; U)'
        );
        $opera9 = new Browser(
            'Opera/9.80 (Windows NT 6.1; U; en) Presto/2.10.229 Version/11.60'
        );

        $this->assertTrue($opera7->hasQuirk('double_linebreak_textarea'));
        $this->assertTrue($opera9->hasQuirk('double_linebreak_textarea'));
    }

    /**
     * Test Opera 6 does not have double linebreak quirk.
     */
    public function testOpera6NoDoubleLinebreakQuirk(): void
    {
        $browser = new Browser(
            'Opera/6.0 (Windows NT 5.0; U)'
        );

        $this->assertFalse($browser->hasQuirk('double_linebreak_textarea'));
    }

    /**
     * Test mobile browsers have avoid popup quirk.
     */
    public function testMobileAvoidPopupQuirk(): void
    {
        $iphone = new Browser(
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        );
        $android = new Browser(
            'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 Mobile Safari/537.36'
        );
        $windowsPhone = new Browser(
            'Mozilla/4.0 (compatible; MSIE 7.0; Windows Phone OS 7.0)'
        );

        $this->assertTrue($iphone->hasQuirk('avoid_popup_windows'));
        $this->assertTrue($android->hasQuirk('avoid_popup_windows'));
        $this->assertTrue($windowsPhone->hasQuirk('avoid_popup_windows'));
    }

    /**
     * Test desktop browsers do not have avoid popup quirk.
     */
    public function testDesktopNoAvoidPopupQuirk(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0'
        );

        $this->assertFalse($browser->hasQuirk('avoid_popup_windows'));
    }

    /**
     * Test custom quirk setting via setQuirk.
     */
    public function testCustomQuirkSetting(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0'
        );

        // Should be able to set custom quirks
        $browser->setQuirk('custom_quirk', true);
        $this->assertTrue($browser->hasQuirk('custom_quirk'));

        $browser->setQuirk('custom_quirk', false);
        $this->assertFalse($browser->hasQuirk('custom_quirk'));
    }

    /**
     * Test quirk value retrieval with getQuirk.
     */
    public function testQuirkValueRetrieval(): void
    {
        $browser = new Browser(
            'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)'
        );

        // Should be able to get quirk values
        $cacheQuirk = $browser->getQuirk('cache_ssl_downloads');
        $this->assertTrue($cacheQuirk);

        $nonexistent = $browser->getQuirk('nonexistent_quirk');
        $this->assertNull($nonexistent);
    }

    /**
     * Test IE modern versions still have some quirks.
     */
    public function testIE11StillHasQuirks(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko'
        );

        // Even IE 11 has some persistent quirks
        $this->assertTrue($browser->hasQuirk('cache_ssl_downloads'));
        $this->assertTrue($browser->hasQuirk('cache_same_url'));
        $this->assertTrue($browser->hasQuirk('break_disposition_filename'));

        // But not the old quirks
        $this->assertFalse($browser->hasQuirk('png_transparency'));
        $this->assertFalse($browser->hasQuirk('scrollbar_in_way'));
        $this->assertFalse($browser->hasQuirk('windowed_controls'));
    }
}
