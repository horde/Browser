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

namespace Horde\Browser\Test;

use Horde\Browser\Browser;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for browser feature detection.
 *
 * Tests cover the Alpha4 behavior that was lost in refactoring:
 * - Default feature set (frames, html, images, java, javascript, tables, css, dom, etc.)
 * - Version-based feature values
 * - Browser-specific constraints
 * - Mobile browser constraints
 * - UTF detection from Accept-Charset
 *
 * @category Horde
 * @package  Browser
 */
#[CoversClass(Browser::class)]
class FeatureDetectionTest extends TestCase
{
    /**
     * Test default features for modern browsers.
     */
    public function testModernBrowserDefaultFeatures(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0'
        );

        // Core features should be present
        $this->assertTrue($browser->hasFeature('javascript'));
        $this->assertTrue($browser->hasFeature('ajax'));
        $this->assertTrue($browser->hasFeature('dom'));
        $this->assertTrue($browser->hasFeature('xmlhttpreq'));

        // Alpha4 default features
        $this->assertTrue($browser->hasFeature('frames'));
        $this->assertTrue($browser->hasFeature('html'));
        $this->assertTrue($browser->hasFeature('images'));
        $this->assertTrue($browser->hasFeature('tables'));
        $this->assertTrue($browser->hasFeature('css'));
    }

    /**
     * Test MSIE 5 Mac feature constraints.
     */
    public function testMSIE5MacFeatures(): void
    {
        $browser = new Browser(
            'Mozilla/4.0 (compatible; MSIE 5.0; Mac_PowerPC)'
        );

        // MSIE 5 Mac had limited JavaScript
        $this->assertSame('1.2', $browser->getFeature('javascript'));
        $this->assertFalse($browser->hasFeature('ajax'));
        $this->assertFalse($browser->hasFeature('dataurl'));
        $this->assertFalse($browser->hasFeature('rte'));
    }

    /**
     * Test MSIE 5 Windows feature constraints.
     */
    public function testMSIE5WindowsFeatures(): void
    {
        $browser = new Browser(
            'Mozilla/4.0 (compatible; MSIE 5.0; Windows NT 5.0)'
        );

        // MSIE 5 Windows had better JavaScript than Mac
        $this->assertSame('1.4', $browser->getFeature('javascript'));
        $this->assertTrue($browser->hasFeature('dom'));
        $this->assertTrue($browser->hasFeature('rte'));
        $this->assertFalse($browser->hasFeature('ajax'));
    }

    /**
     * Test MSIE 6 feature set.
     */
    public function testMSIE6Features(): void
    {
        $browser = new Browser(
            'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)'
        );

        $this->assertSame('1.4', $browser->getFeature('javascript'));
        $this->assertTrue($browser->hasFeature('dom'));
        $this->assertTrue($browser->hasFeature('rte'));
        $this->assertTrue($browser->hasFeature('optgroup'));
        $this->assertFalse($browser->hasFeature('ajax'));  // No AJAX in IE6
    }

    /**
     * Test MSIE 7 feature set (AJAX support added).
     */
    public function testMSIE7Features(): void
    {
        $browser = new Browser(
            'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)'
        );

        $this->assertSame('1.4', $browser->getFeature('javascript'));
        $this->assertTrue($browser->hasFeature('ajax'));  // AJAX added in IE7
        $this->assertTrue($browser->hasFeature('dom'));
        $this->assertTrue($browser->hasFeature('rte'));
        $this->assertFalse($browser->hasFeature('dataurl'));  // Still no data URLs
    }

    /**
     * Test MSIE 8 feature set (limited data URL support).
     */
    public function testMSIE8Features(): void
    {
        $browser = new Browser(
            'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)'
        );

        $this->assertSame('1.4', $browser->getFeature('javascript'));
        $this->assertTrue($browser->hasFeature('ajax'));
        $this->assertTrue($browser->hasFeature('dom'));
        $this->assertTrue($browser->hasFeature('rte'));
        $this->assertSame(32768, $browser->getFeature('dataurl'));  // 32KB limit
    }

    /**
     * Test MSIE 9+ feature set (full modern features).
     */
    public function testMSIE9PlusFeatures(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)'
        );

        $this->assertSame('1.4', $browser->getFeature('javascript'));
        $this->assertTrue($browser->hasFeature('ajax'));
        $this->assertTrue($browser->hasFeature('dom'));
        $this->assertTrue($browser->hasFeature('cite'));
        $this->assertTrue($browser->hasFeature('dataurl'));  // Full data URL support
    }

    /**
     * Test Opera 6 feature constraints.
     */
    public function testOpera6Features(): void
    {
        $browser = new Browser(
            'Opera/6.0 (Windows NT 5.0; U)'
        );

        $this->assertSame('1.5', $browser->getFeature('javascript'));
        $this->assertFalse($browser->hasFeature('ajax'));
        $this->assertFalse($browser->hasFeature('dom'));
        $this->assertTrue($browser->hasFeature('iframes'));
    }

    /**
     * Test Opera 7 feature set (DOM added).
     */
    public function testOpera7Features(): void
    {
        $browser = new Browser(
            'Opera/7.0 (Windows NT 5.1; U)'
        );

        $this->assertSame('1.5', $browser->getFeature('javascript'));
        $this->assertTrue($browser->hasFeature('dom'));  // DOM added in Opera 7
        $this->assertTrue($browser->hasFeature('iframes'));
        $this->assertFalse($browser->hasFeature('ajax'));
    }

    /**
     * Test Opera 9+ feature set (AJAX support).
     */
    public function testOpera9PlusFeatures(): void
    {
        $browser = new Browser(
            'Opera/9.80 (Windows NT 6.1; U; en) Presto/2.10.229 Version/11.60'
        );

        $this->assertSame('1.5', $browser->getFeature('javascript'));
        $this->assertTrue($browser->hasFeature('ajax'));  // AJAX added in Opera 9
        $this->assertTrue($browser->hasFeature('dom'));
        $this->assertSame(4100, $browser->getFeature('dataurl'));  // 4KB limit
    }

    /**
     * Test Firefox feature set.
     */
    public function testFirefoxFeatures(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0'
        );

        $this->assertTrue($browser->hasFeature('javascript'));
        $this->assertTrue($browser->hasFeature('ajax'));
        $this->assertTrue($browser->hasFeature('dom'));
        $this->assertTrue($browser->hasFeature('rte'));
    }

    /**
     * Test Chrome/Safari feature set.
     */
    public function testWebkitFeatures(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0'
        );

        $this->assertTrue($browser->hasFeature('javascript'));
        $this->assertTrue($browser->hasFeature('ajax'));
        $this->assertTrue($browser->hasFeature('dom'));
        $this->assertTrue($browser->hasFeature('rte'));
    }

    /**
     * Test Windows Phone mobile feature constraints.
     */
    public function testWindowsPhoneMobileConstraints(): void
    {
        $browser = new Browser(
            'Mozilla/4.0 (compatible; MSIE 7.0; Windows Phone OS 7.0)'
        );

        // Windows Phone 6-7 had limited features
        $this->assertFalse($browser->hasFeature('frames'));
        $this->assertFalse($browser->hasFeature('javascript'));
    }

    /**
     * Test Opera Mini mobile constraints.
     */
    public function testOperaMiniConstraints(): void
    {
        $browser = new Browser(
            'Opera/9.80 (J2ME/MIDP; Opera Mini/9.80; U; en) Presto/2.8.119 Version/11.10'
        );

        // Opera Mini had some constraints
        $this->assertFalse($browser->hasFeature('frames'));
        // JavaScript may be enabled or disabled depending on mode
    }

    /**
     * Test UTF feature detection from Accept-Charset header.
     */
    public function testUTFFeatureFromAcceptCharset(): void
    {
        // Mock $_SERVER['HTTP_ACCEPT_CHARSET']
        $_SERVER['HTTP_ACCEPT_CHARSET'] = 'utf-8, iso-8859-1;q=0.9';

        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0'
        );

        $this->assertTrue($browser->hasFeature('utf'));

        // Cleanup
        unset($_SERVER['HTTP_ACCEPT_CHARSET']);
    }

    /**
     * Test no UTF feature without Accept-Charset header.
     */
    public function testNoUTFWithoutAcceptCharset(): void
    {
        // Ensure no Accept-Charset header
        unset($_SERVER['HTTP_ACCEPT_CHARSET']);

        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0'
        );

        // Without Accept-Charset, utf feature should not be set
        // (or default to false/not present)
        $this->assertFalse($browser->hasFeature('utf'));
    }

    /**
     * Test custom feature setting via setFeature.
     */
    public function testCustomFeatureSetting(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0'
        );

        // Should be able to override features
        $browser->setFeature('custom_feature', true);
        $this->assertTrue($browser->hasFeature('custom_feature'));

        $browser->setFeature('custom_feature', false);
        $this->assertFalse($browser->hasFeature('custom_feature'));
    }

    /**
     * Test feature value retrieval with getFeature.
     */
    public function testFeatureValueRetrieval(): void
    {
        $browser = new Browser(
            'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)'
        );

        // Should return the actual value, not just boolean
        $dataurl = $browser->getFeature('dataurl');
        $this->assertSame(32768, $dataurl);
    }
}
