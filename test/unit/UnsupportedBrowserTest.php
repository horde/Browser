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
 * Tests for unsupported browser version detection.
 *
 * Tests ensure that mainstream browsers in ancient versions
 * (pre-September 2017) that lack CSS Grid, CSS Variables, and ES6 Modules
 * are properly flagged as unsupported for responsive UI.
 *
 * @category Horde
 * @package  Browser
 */
#[CoversClass(Browser::class)]
class UnsupportedBrowserTest extends TestCase
{
    /**
     * Test Internet Explorer (all versions unsupported).
     */
    public function testInternetExplorerAlwaysUnsupported(): void
    {
        $ie6 = new Browser('Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)');
        $ie7 = new Browser('Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)');
        $ie8 = new Browser('Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)');
        $ie9 = new Browser('Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)');
        $ie10 = new Browser('Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Trident/6.0)');
        $ie11 = new Browser('Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko');

        // All IE versions are unsupported
        $this->assertTrue($ie6->hasFeature('unsupported_browser_version'));
        $this->assertTrue($ie7->hasFeature('unsupported_browser_version'));
        $this->assertTrue($ie8->hasFeature('unsupported_browser_version'));
        $this->assertTrue($ie9->hasFeature('unsupported_browser_version'));
        $this->assertTrue($ie10->hasFeature('unsupported_browser_version'));
        $this->assertTrue($ie11->hasFeature('unsupported_browser_version'));
    }

    /**
     * Test Edge Legacy (EdgeHTML) version boundaries.
     */
    public function testEdgeLegacyVersions(): void
    {
        // Edge Legacy < 16 is unsupported
        $edge15 = new Browser('Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36 Edge/15.15063');
        $this->assertTrue($edge15->hasFeature('unsupported_browser_version'));

        // Edge 16+ is supported
        $edge16 = new Browser('Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36 Edge/16.16299');
        $this->assertFalse($edge16->hasFeature('unsupported_browser_version'));

        // Edge 18 (last EdgeHTML) is supported
        $edge18 = new Browser('Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.18363');
        $this->assertFalse($edge18->hasFeature('unsupported_browser_version'));

        // Edge Chromium 79+ is supported
        $edge79 = new Browser('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.74 Safari/537.36 Edg/79.0.309.43');
        $this->assertFalse($edge79->hasFeature('unsupported_browser_version'));
    }

    /**
     * Test Chrome version boundaries.
     */
    public function testChromeVersions(): void
    {
        // Chrome < 61 is unsupported
        $chrome50 = new Browser('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36');
        $this->assertTrue($chrome50->hasFeature('unsupported_browser_version'));

        $chrome60 = new Browser('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36');
        $this->assertTrue($chrome60->hasFeature('unsupported_browser_version'));

        // Chrome 61+ is supported (September 2017)
        $chrome61 = new Browser('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36');
        $this->assertFalse($chrome61->hasFeature('unsupported_browser_version'));

        // Modern Chrome is supported
        $chrome120 = new Browser('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        $this->assertFalse($chrome120->hasFeature('unsupported_browser_version'));
    }

    /**
     * Test Firefox version boundaries.
     */
    public function testFirefoxVersions(): void
    {
        // Firefox < 60 is unsupported
        $firefox50 = new Browser('Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:50.0) Gecko/20100101 Firefox/50.0');
        $this->assertTrue($firefox50->hasFeature('unsupported_browser_version'));

        $firefox59 = new Browser('Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:59.0) Gecko/20100101 Firefox/59.0');
        $this->assertTrue($firefox59->hasFeature('unsupported_browser_version'));

        // Firefox 60+ is supported (May 2018)
        $firefox60 = new Browser('Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:60.0) Gecko/20100101 Firefox/60.0');
        $this->assertFalse($firefox60->hasFeature('unsupported_browser_version'));

        // Modern Firefox is supported
        $firefox121 = new Browser('Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0');
        $this->assertFalse($firefox121->hasFeature('unsupported_browser_version'));
    }

    /**
     * Test Safari version boundaries.
     */
    public function testSafariVersions(): void
    {
        // Safari < 11 is unsupported
        $safari9 = new Browser('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/601.7.7 (KHTML, like Gecko) Version/9.1.2 Safari/601.7.7');
        $this->assertTrue($safari9->hasFeature('unsupported_browser_version'));

        $safari10 = new Browser('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/603.3.8 (KHTML, like Gecko) Version/10.1.2 Safari/603.3.8');
        $this->assertTrue($safari10->hasFeature('unsupported_browser_version'));

        // Safari 11+ is supported (September 2017)
        $safari11 = new Browser('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/11.1.2 Safari/605.1.15');
        $this->assertFalse($safari11->hasFeature('unsupported_browser_version'));

        // Modern Safari is supported
        $safari17 = new Browser('Mozilla/5.0 (Macintosh; Intel Mac OS X 14_2) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15');
        $this->assertFalse($safari17->hasFeature('unsupported_browser_version'));
    }

    /**
     * Test Opera version boundaries.
     */
    public function testOperaVersions(): void
    {
        // Opera < 48 is unsupported
        $opera40 = new Browser('Opera/9.80 (Windows NT 6.1; U; en) Presto/2.10.229 Version/40.0');
        $this->assertTrue($opera40->hasFeature('unsupported_browser_version'));

        $opera47 = new Browser('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.78 Safari/537.36 OPR/47.0.2631.39');
        $this->assertTrue($opera47->hasFeature('unsupported_browser_version'));

        // Opera 48+ is supported (September 2017)
        $opera48 = new Browser('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36 OPR/48.0.2685.52');
        $this->assertFalse($opera48->hasFeature('unsupported_browser_version'));

        // Modern Opera is supported
        $opera95 = new Browser('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36 OPR/95.0.0.0');
        $this->assertFalse($opera95->hasFeature('unsupported_browser_version'));
    }

    /**
     * Test that unknown browsers are NOT flagged as unsupported.
     */
    public function testUnknownBrowsersNotFlagged(): void
    {
        // Custom user agent
        $custom = new Browser('MyCustomBrowser/1.0');
        $this->assertFalse($custom->hasFeature('unsupported_browser_version'));

        // Text browser (Lynx)
        $lynx = new Browser('Lynx/2.8.9rel.1 libwww-FM/2.14 SSL-MM/1.4.1');
        $this->assertFalse($lynx->hasFeature('unsupported_browser_version'));

        // Bot/crawler
        $googlebot = new Browser('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
        $this->assertFalse($googlebot->hasFeature('unsupported_browser_version'));

        // Empty user agent
        $empty = new Browser('');
        $this->assertFalse($empty->hasFeature('unsupported_browser_version'));
    }

    /**
     * Test mobile browser versions.
     */
    public function testMobileBrowserVersions(): void
    {
        // Old Chrome Mobile (unsupported)
        $chromeAndroid50 = new Browser('Mozilla/5.0 (Linux; Android 7.0; SM-G930F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.89 Mobile Safari/537.36');
        $this->assertTrue($chromeAndroid50->hasFeature('unsupported_browser_version'));

        // Chrome Mobile 61+ (supported)
        $chromeAndroid70 = new Browser('Mozilla/5.0 (Linux; Android 8.0.0; SM-G960F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Mobile Safari/537.36');
        $this->assertFalse($chromeAndroid70->hasFeature('unsupported_browser_version'));

        // Old Safari iOS (unsupported)
        $ios10 = new Browser('Mozilla/5.0 (iPhone; CPU iPhone OS 10_3_3 like Mac OS X) AppleWebKit/603.3.8 (KHTML, like Gecko) Version/10.0 Mobile/14G60 Safari/602.1');
        $this->assertTrue($ios10->hasFeature('unsupported_browser_version'));

        // Safari iOS 11+ (supported)
        $ios11 = new Browser('Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1');
        $this->assertFalse($ios11->hasFeature('unsupported_browser_version'));
    }

    /**
     * Test edge case: version 0 (Unknown browser).
     */
    public function testUnknownVersion(): void
    {
        // If browser detection fails, it becomes "Unknown" browser
        // Unknown browsers are NOT flagged as unsupported
        $chromeNoVersion = new Browser('Chrome');
        $this->assertFalse($chromeNoVersion->hasFeature('unsupported_browser_version'));

        // Same for other ambiguous user agents
        $noVersion = new Browser('Mozilla');
        $this->assertFalse($noVersion->hasFeature('unsupported_browser_version'));
    }

    /**
     * Test that regular features still work.
     */
    public function testRegularFeaturesStillWork(): void
    {
        $browser = new Browser('Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0');

        // Regular features should work
        $this->assertTrue($browser->hasFeature('javascript'));
        $this->assertTrue($browser->hasFeature('ajax'));
        $this->assertTrue($browser->hasFeature('dom'));

        // And the new feature
        $this->assertFalse($browser->hasFeature('unsupported_browser_version'));
    }
}
