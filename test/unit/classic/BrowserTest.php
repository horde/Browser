<?php

/**
 * Copyright 2024-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL-2.1).
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package  Browser
 */

declare(strict_types=1);

namespace Horde\Browser\Test\Classic;

use Horde_Browser;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the classic Horde_Browser class.
 *
 * @category Horde
 * @package  Browser
 */
#[CoversClass(Horde_Browser::class)]
class BrowserTest extends TestCase
{
    /**
     * Test Chrome browser detection (wrapper: minor version now integer)
     */
    public function testChromeDetection(): void
    {
        $browser = new Horde_Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        );

        $this->assertTrue($browser->isBrowser('webkit'));
        $this->assertSame('webkit', $browser->getBrowser());
        $this->assertSame('120', $browser->getMajor());
        $this->assertSame(0, $browser->getMinor()); // Wrapper improvement: clean integer
        $this->assertFalse($browser->isMobile());
        $this->assertFalse($browser->isRobot());
    }

    /**
     * Test Firefox browser detection (wrapper: now returns actual Firefox version!)
     */
    public function testFirefoxDetection(): void
    {
        $browser = new Horde_Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0'
        );

        $this->assertTrue($browser->isBrowser('mozilla'));
        $this->assertSame('mozilla', $browser->getBrowser());
        $this->assertSame('121', $browser->getMajor()); // Wrapper improvement: actual Firefox version!
        $this->assertSame(0, $browser->getMinor());
        $this->assertFalse($browser->isMobile());
        $this->assertFalse($browser->isRobot());
    }

    /**
     * Test Safari browser detection (legacy detects as webkit)
     */
    public function testSafariDetection(): void
    {
        $browser = new Horde_Browser(
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_2) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15'
        );

        $this->assertTrue($browser->isBrowser('webkit'));
        $this->assertSame('webkit', $browser->getBrowser());
        $this->assertSame('17', $browser->getMajor());
        $this->assertSame(2, $browser->getMinor());
        $this->assertFalse($browser->isMobile());
    }

    /**
     * Test Edge browser detection (legacy detects as webkit)
     */
    public function testEdgeDetection(): void
    {
        $browser = new Horde_Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0'
        );

        $this->assertTrue($browser->isBrowser('webkit'));
        $this->assertSame('webkit', $browser->getBrowser());
        $this->assertFalse($browser->isMobile());
    }

    /**
     * Test Internet Explorer detection
     */
    public function testInternetExplorerDetection(): void
    {
        $browser = new Horde_Browser(
            'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko'
        );

        $this->assertTrue($browser->isBrowser('msie'));
        $this->assertSame('msie', $browser->getBrowser());
        $this->assertSame('11', $browser->getMajor()); // Legacy returns string
    }

    /**
     * Test Opera browser detection (modern Opera detected as webkit by legacy)
     */
    public function testOperaDetection(): void
    {
        $browser = new Horde_Browser(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 OPR/106.0.0.0'
        );

        $this->assertTrue($browser->isBrowser('webkit')); // Modern Opera uses Chromium
        $this->assertSame('webkit', $browser->getBrowser());
    }

    /**
     * Test iPhone mobile detection (legacy doesn't distinguish iPhone platform)
     */
    public function testIPhoneMobileDetection(): void
    {
        $browser = new Horde_Browser(
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1'
        );

        $this->assertTrue($browser->isMobile());
        $this->assertFalse($browser->isTablet());
        $this->assertSame('webkit', $browser->getBrowser());
        $this->assertSame('mac', $browser->getPlatform()); // Legacy doesn't distinguish
    }

    /**
     * Test iPad tablet detection (wrapper: improved tablet detection!)
     */
    public function testIPadTabletDetection(): void
    {
        $browser = new Horde_Browser(
            'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1'
        );

        $this->assertTrue($browser->isMobile());
        $this->assertTrue($browser->isTablet()); // Wrapper improvement: iPad now detected!
        $this->assertSame('mac', $browser->getPlatform());
    }

    /**
     * Test Android phone mobile detection (wrapper: unchanged)
     */
    public function testAndroidPhoneMobileDetection(): void
    {
        $browser = new Horde_Browser(
            'Mozilla/5.0 (Linux; Android 13; SM-S908B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36'
        );

        $this->assertTrue($browser->isMobile());
        $this->assertFalse($browser->isTablet());
        $this->assertSame('unix', $browser->getPlatform());
    }

    /**
     * Test Android tablet detection (wrapper: improved tablet detection!)
     */
    public function testAndroidTabletDetection(): void
    {
        $browser = new Horde_Browser(
            'Mozilla/5.0 (Linux; Android 13; SM-X906C) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        );

        $this->assertTrue($browser->isMobile());
        $this->assertTrue($browser->isTablet()); // Wrapper improvement: Android tablet detected!
        $this->assertSame('unix', $browser->getPlatform());
    }

    /**
     * Test robot detection
     */
    #[DataProvider('robotUserAgentProvider')]
    public function testRobotDetection(string $userAgent, string $expectedName): void
    {
        $browser = new Horde_Browser($userAgent);

        $this->assertTrue($browser->isRobot(), "Should detect {$expectedName} as robot");
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
     * Test platform detection
     */
    #[DataProvider('platformProvider')]
    public function testPlatformDetection(string $userAgent, string $expectedPlatform): void
    {
        $browser = new Horde_Browser($userAgent);

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
                'win',
            ],
            'macOS' => [
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_2) AppleWebKit/605.1.15',
                'mac',
            ],
            'Linux' => [
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
                'unix',
            ],
            // Legacy doesn't distinguish these properly
            // 'iPhone' => [...],
            // 'iPad' => [...],
            // 'Android' => [...],
        ];
    }

    /**
     * Test version string parsing (wrapper: clean integer versions)
     */
    public function testVersionParsing(): void
    {
        $browser = new Horde_Browser(
            'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 Chrome/120.5.6543 Safari/537.36'
        );

        $this->assertSame('120', $browser->getMajor()); // String for BC
        $this->assertSame(5, $browser->getMinor()); // Wrapper improvement: clean integer!
        $this->assertStringContainsString('120', $browser->getVersion());
    }

    /**
     * Test features can be set and retrieved
     */
    public function testFeatureSetAndGet(): void
    {
        $browser = new Horde_Browser(
            'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 Chrome/120.0.0.0'
        );

        $browser->setFeature('custom_feature', true);
        $this->assertTrue($browser->hasFeature('custom_feature'));
        $this->assertTrue($browser->getFeature('custom_feature'));

        $browser->setFeature('another_feature', 'value');
        $this->assertTrue($browser->hasFeature('another_feature'));
        $this->assertSame('value', $browser->getFeature('another_feature'));
    }

    /**
     * Test quirks can be set and retrieved
     */
    public function testQuirkSetAndGet(): void
    {
        $browser = new Horde_Browser(
            'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36'
        );

        $browser->setQuirk('custom_quirk', true);
        $this->assertTrue($browser->hasQuirk('custom_quirk'));
        $this->assertTrue($browser->getQuirk('custom_quirk'));

        $browser->setQuirk('another_quirk', 'quirk_value');
        $this->assertTrue($browser->hasQuirk('another_quirk'));
        $this->assertSame('quirk_value', $browser->getQuirk('another_quirk'));
    }

    /**
     * Test agent string retrieval
     */
    public function testAgentStringRetrieval(): void
    {
        $userAgent = 'Mozilla/5.0 (Test Agent String)';
        $browser = new Horde_Browser($userAgent);

        $this->assertSame($userAgent, $browser->getAgentString());
    }

    /**
     * Test browser can be manually set
     */
    public function testManualBrowserSet(): void
    {
        $browser = new Horde_Browser();
        $browser->setBrowser('custom_browser');

        $this->assertTrue($browser->isBrowser('custom_browser'));
        $this->assertSame('custom_browser', $browser->getBrowser());
    }

    /**
     * Test mobile flag can be manually set
     */
    public function testManualMobileSet(): void
    {
        $browser = new Horde_Browser(
            'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36'
        );

        $this->assertFalse($browser->isMobile());

        $browser->setMobile(true);
        $this->assertTrue($browser->isMobile());

        $browser->setMobile(false);
        $this->assertFalse($browser->isMobile());
    }

    /**
     * Test tablet flag can be manually set
     */
    public function testManualTabletSet(): void
    {
        $browser = new Horde_Browser(
            'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36'
        );

        $this->assertFalse($browser->isTablet());

        $browser->setTablet(true);
        $this->assertTrue($browser->isTablet());

        $browser->setTablet(false);
        $this->assertFalse($browser->isTablet());
    }

    /**
     * Test empty user agent handling
     */
    public function testEmptyUserAgent(): void
    {
        $browser = new Horde_Browser('');

        $this->assertSame('', $browser->getBrowser());
        $this->assertSame('0', $browser->getMajor()); // String for BC
        $this->assertSame(0, $browser->getMinor());
        $this->assertFalse($browser->isMobile());
        $this->assertFalse($browser->isRobot());
    }
}
