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

use Horde\Browser\{Browser, BrowserFamily, Platform};
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the modern Horde\Browser class.
 *
 * @category Horde
 * @package  Browser
 */
#[CoversClass(Browser::class)]
#[CoversClass(BrowserFamily::class)]
#[CoversClass(Platform::class)]
class BrowserTest extends TestCase
{
    /**
     * Test Chrome browser detection (improved from legacy)
     */
    public function testChromeDetection(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        );

        $this->assertTrue($browser->isBrowser(BrowserFamily::Chrome));
        $this->assertSame(BrowserFamily::Chrome, $browser->getBrowser());
        $this->assertSame('chrome', $browser->getBrowserName());
        $this->assertSame(120, $browser->getMajorVersion());
        $this->assertSame(0, $browser->getMinorVersion());
        $this->assertFalse($browser->mobile());
        $this->assertFalse($browser->robot());
    }

    /**
     * Test Firefox browser detection (improved from legacy)
     */
    public function testFirefoxDetection(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0'
        );

        $this->assertTrue($browser->isBrowser(BrowserFamily::Firefox));
        $this->assertSame(BrowserFamily::Firefox, $browser->getBrowser());
        $this->assertSame('firefox', $browser->getBrowserName());
        $this->assertSame(121, $browser->getMajorVersion());
        $this->assertSame(0, $browser->getMinorVersion());
        $this->assertFalse($browser->mobile());
        $this->assertFalse($browser->robot());
    }

    /**
     * Test Safari browser detection (improved from legacy)
     */
    public function testSafariDetection(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_2) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15'
        );

        $this->assertTrue($browser->isBrowser(BrowserFamily::Safari));
        $this->assertSame(BrowserFamily::Safari, $browser->getBrowser());
        $this->assertSame('safari', $browser->getBrowserName());
        $this->assertSame(17, $browser->getMajorVersion());
        $this->assertSame(2, $browser->getMinorVersion());
        $this->assertFalse($browser->mobile());
    }

    /**
     * Test Edge browser detection (improved from legacy)
     */
    public function testEdgeDetection(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0'
        );

        $this->assertTrue($browser->isBrowser(BrowserFamily::Edge));
        $this->assertSame(BrowserFamily::Edge, $browser->getBrowser());
        $this->assertSame('edge', $browser->getBrowserName());
        $this->assertSame(120, $browser->getMajorVersion());
        $this->assertSame(0, $browser->getMinorVersion());
        $this->assertFalse($browser->mobile());
    }

    /**
     * Test Internet Explorer detection
     */
    public function testInternetExplorerDetection(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko'
        );

        $this->assertTrue($browser->isBrowser(BrowserFamily::InternetExplorer));
        $this->assertSame(BrowserFamily::InternetExplorer, $browser->getBrowser());
        $this->assertSame(11, $browser->getMajorVersion());
        $this->assertSame(0, $browser->getMinorVersion());
    }

    /**
     * Test Opera browser detection (improved from legacy)
     */
    public function testOperaDetection(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 OPR/106.0.0.0'
        );

        $this->assertTrue($browser->isBrowser(BrowserFamily::Opera));
        $this->assertSame(BrowserFamily::Opera, $browser->getBrowser());
        $this->assertSame('opera', $browser->getBrowserName());
        $this->assertSame(106, $browser->getMajorVersion());
    }

    /**
     * Test iPhone mobile detection (improved from legacy)
     */
    public function testIPhoneMobileDetection(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1'
        );

        $this->assertTrue($browser->mobile());
        $this->assertFalse($browser->tablet());
        $this->assertSame(BrowserFamily::Safari, $browser->getBrowser());
        $this->assertSame(Platform::IPhone, $browser->getPlatform()); // Improved!
        $this->assertSame('iphone', $browser->getPlatformName());
    }

    /**
     * Test iPad tablet detection (improved from legacy)
     */
    public function testIPadTabletDetection(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1'
        );

        $this->assertTrue($browser->mobile());
        $this->assertTrue($browser->tablet()); // Improved!
        $this->assertSame(Platform::IPad, $browser->getPlatform()); // Improved!
        $this->assertSame('ipad', $browser->getPlatformName());
    }

    /**
     * Test Android phone mobile detection (improved from legacy)
     */
    public function testAndroidPhoneMobileDetection(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Linux; Android 13; SM-S908B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36'
        );

        $this->assertTrue($browser->mobile());
        $this->assertFalse($browser->tablet());
        $this->assertSame(Platform::Android, $browser->getPlatform()); // Improved!
        $this->assertSame('android', $browser->getPlatformName());
    }

    /**
     * Test Android tablet detection (improved from legacy)
     */
    public function testAndroidTabletDetection(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Linux; Android 13; SM-X906C) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        );

        $this->assertTrue($browser->mobile());
        $this->assertTrue($browser->tablet()); // Improved!
        $this->assertSame(Platform::Android, $browser->getPlatform()); // Improved!
        $this->assertSame('android', $browser->getPlatformName());
    }

    /**
     * Test robot detection
     */
    #[DataProvider('robotUserAgentProvider')]
    public function testRobotDetection(string $userAgent, string $expectedName): void
    {
        $browser = new Browser($userAgent);

        $this->assertTrue($browser->robot(), "Should detect {$expectedName} as robot");
    }

    /**
     * Provider for robot user agents
     */
    public static function robotUserAgentProvider(): array
    {
        return [
            'Googlebot' => [
                'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
                'Googlebot',
            ],
            'Bingbot' => [
                'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
                'Bingbot',
            ],
            'Yahoo Slurp' => [
                'Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp)',
                'Slurp',
            ],
            'Baiduspider' => [
                'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)',
                'Baiduspider',
            ],
        ];
    }

    /**
     * Test platform detection (all improved from legacy)
     */
    #[DataProvider('platformProvider')]
    public function testPlatformDetection(string $userAgent, Platform $expectedPlatform): void
    {
        $browser = new Browser($userAgent);

        $this->assertSame($expectedPlatform, $browser->getPlatform());
    }

    /**
     * Provider for platform detection tests
     */
    public static function platformProvider(): array
    {
        return [
            'Windows 10' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                Platform::Windows,
            ],
            'macOS' => [
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_2) AppleWebKit/605.1.15',
                Platform::MacOS,
            ],
            'Linux' => [
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
                Platform::Linux,
            ],
            'iPhone' => [
                'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15',
                Platform::IPhone,
            ],
            'iPad' => [
                'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15',
                Platform::IPad,
            ],
            'Android' => [
                'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36',
                Platform::Android,
            ],
        ];
    }

    /**
     * Test version parsing (improved - returns integers)
     */
    public function testVersionParsing(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 Chrome/120.5 Safari/537.36'
        );

        $this->assertSame(120, $browser->getMajorVersion()); // Now integer!
        $this->assertSame(5, $browser->getMinorVersion()); // Now integer!
        $this->assertSame('120.5', $browser->getVersion());
    }

    /**
     * Test features can be retrieved
     */
    public function testFeatureRetrieval(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 Chrome/120.0.0.0'
        );

        $this->assertTrue($browser->hasFeature('javascript'));
        $this->assertTrue($browser->getFeature('javascript'));

        $this->assertTrue($browser->hasFeature('ajax'));
        $this->assertTrue($browser->hasFeature('dom'));
        $this->assertTrue($browser->hasFeature('xmlhttpreq'));
    }

    /**
     * Test quirks can be retrieved
     */
    public function testQuirkRetrieval(): void
    {
        $mobileBrowser = new Browser(
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15'
        );

        $this->assertTrue($mobileBrowser->hasQuirk('avoid_popup_windows'));
        $this->assertTrue($mobileBrowser->getQuirk('avoid_popup_windows'));
    }

    /**
     * Test user agent string retrieval
     */
    public function testUserAgentRetrieval(): void
    {
        $userAgent = 'Mozilla/5.0 (Test Agent String)';
        $browser = new Browser($userAgent);

        $this->assertSame($userAgent, $browser->getUserAgent());
    }

    /**
     * Test empty user agent handling
     */
    public function testEmptyUserAgent(): void
    {
        $browser = new Browser('');

        $this->assertSame(BrowserFamily::Unknown, $browser->getBrowser());
        $this->assertSame(0, $browser->getMajorVersion());
        $this->assertSame(0, $browser->getMinorVersion());
        $this->assertFalse($browser->mobile());
        $this->assertFalse($browser->robot());
    }

    /**
     * Test platform helper methods
     */
    public function testPlatformHelperMethods(): void
    {
        $this->assertTrue(Platform::Windows->isDesktop());
        $this->assertFalse(Platform::Windows->isMobile());
        $this->assertFalse(Platform::Windows->isTablet());

        $this->assertTrue(Platform::IPhone->isMobile());
        $this->assertFalse(Platform::IPhone->isTablet());
        $this->assertFalse(Platform::IPhone->isDesktop());

        $this->assertTrue(Platform::IPad->isTablet());
        $this->assertFalse(Platform::IPad->isDesktop());

        $this->assertSame('Windows', Platform::Windows->displayName());
        $this->assertSame('iPhone', Platform::IPhone->displayName());
    }

    /**
     * Test browser family display names
     */
    public function testBrowserFamilyDisplayNames(): void
    {
        $this->assertSame('Chrome', BrowserFamily::Chrome->displayName());
        $this->assertSame('Firefox', BrowserFamily::Firefox->displayName());
        $this->assertSame('Microsoft Edge', BrowserFamily::Edge->displayName());
    }

    /**
     * Test native browser values (not legacy 'webkit')
     */
    public function testNativeBrowserValues(): void
    {
        $chrome = new Browser(
            'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        );
        $safari = new Browser(
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_2) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15'
        );
        $edge = new Browser(
            'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0'
        );

        // Native: Each browser is distinct
        $this->assertSame(BrowserFamily::Chrome, $chrome->getBrowser());
        $this->assertSame(BrowserFamily::Safari, $safari->getBrowser());
        $this->assertSame(BrowserFamily::Edge, $edge->getBrowser());

        // Native string values: Specific browser names
        $this->assertSame('chrome', $chrome->getBrowserName());
        $this->assertSame('safari', $safari->getBrowserName());
        $this->assertSame('edge', $edge->getBrowserName());

        // All different! (Legacy would return 'webkit' for all three)
        $this->assertNotSame($chrome->getBrowser(), $safari->getBrowser());
        $this->assertNotSame($chrome->getBrowser(), $edge->getBrowser());
        $this->assertNotSame($safari->getBrowser(), $edge->getBrowser());
    }

    /**
     * Test native platform values (not legacy 'mac' for iPhone)
     */
    public function testNativePlatformValues(): void
    {
        $iphone = new Browser(
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15'
        );
        $ipad = new Browser(
            'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15'
        );
        $mac = new Browser(
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_2) AppleWebKit/605.1.15'
        );

        // Native: Each platform is distinct
        $this->assertSame(Platform::IPhone, $iphone->getPlatform());
        $this->assertSame(Platform::IPad, $ipad->getPlatform());
        $this->assertSame(Platform::MacOS, $mac->getPlatform());

        // Native string values: Specific platform names
        $this->assertSame('iphone', $iphone->getPlatformName());
        $this->assertSame('ipad', $ipad->getPlatformName());
        $this->assertSame('macos', $mac->getPlatformName());

        // All different! (Legacy would return 'mac' for all three)
        $this->assertNotSame($iphone->getPlatform(), $ipad->getPlatform());
        $this->assertNotSame($iphone->getPlatform(), $mac->getPlatform());
        $this->assertNotSame($ipad->getPlatform(), $mac->getPlatform());
    }

    /**
     * Test native integer version numbers (not legacy strings)
     */
    public function testNativeIntegerVersions(): void
    {
        $browser = new Browser(
            'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 Chrome/120.5 Safari/537.36'
        );

        // Native: Returns integers for proper comparison
        $major = $browser->getMajorVersion();
        $minor = $browser->getMinorVersion();

        $this->assertIsInt($major);
        $this->assertIsInt($minor);
        $this->assertSame(120, $major);
        $this->assertSame(5, $minor);

        // Can do proper numeric comparisons!
        $this->assertTrue($major >= 100);
        $this->assertTrue($major < 200);
        $this->assertTrue($minor >= 0);

        // Works with version checks
        if ($major >= 120) {
            $this->assertTrue(true, 'Modern browser version check works');
        }
    }

    /**
     * Test native tablet detection (legacy was broken)
     */
    public function testNativeTabletDetection(): void
    {
        $ipad = new Browser(
            'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15'
        );
        $androidTablet = new Browser(
            'Mozilla/5.0 (Linux; Android 13; SM-X906C) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36'
        );
        $iphone = new Browser(
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15'
        );

        // Native: iPad is correctly detected as tablet
        $this->assertTrue($ipad->tablet(), 'iPad should be detected as tablet');
        $this->assertFalse($ipad->mobile(), 'iPad is not a mobile phone');
        $this->assertSame(Platform::IPad, $ipad->getPlatform());

        // Native: Android tablet is correctly detected
        $this->assertTrue($androidTablet->tablet(), 'Android tablet should be detected');
        $this->assertTrue($androidTablet->mobile(), 'Android is considered mobile platform');
        $this->assertSame(Platform::Android, $androidTablet->getPlatform());

        // iPhone is mobile but not tablet
        $this->assertTrue($iphone->mobile(), 'iPhone is mobile');
        $this->assertFalse($iphone->tablet(), 'iPhone is not a tablet');

        // Legacy would return false for tablet detection (broken detection)
        // Modern properly detects tablets
    }
}
