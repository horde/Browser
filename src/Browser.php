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

namespace Horde\Browser;

/**
 * Modern browser detection and capability information.
 *
 * Provides improved browser, platform, and device type detection
 * with modern user agent parsing.
 *
 * @category Horde
 * @package  Browser
 */
readonly class Browser
{
    /**
     * Browser family.
     */
    public BrowserFamily $browser;

    /**
     * Major version number.
     */
    public int $majorVersion;

    /**
     * Minor version number.
     */
    public int $minorVersion;

    /**
     * Platform.
     */
    public Platform $platform;

    /**
     * Is this a mobile device?
     */
    public bool $isMobile;

    /**
     * Is this a tablet device?
     */
    public bool $isTablet;

    /**
     * Is this a robot/crawler?
     */
    public bool $isRobot;

    /**
     * Full user agent string.
     */
    public string $userAgent;

    /**
     * Features supported by browser.
     *
     * @var array<string, mixed>
     */
    private array $features;

    /**
     * Browser quirks.
     *
     * @var array<string, mixed>
     */
    private array $quirks;

    /**
     * Create browser instance from user agent string.
     *
     * @param string|null $userAgent User agent string (null = detect from $_SERVER)
     * @param string|null $accept    HTTP Accept header (null = detect from $_SERVER)
     */
    public function __construct(?string $userAgent = null, ?string $accept = null)
    {
        $this->userAgent = $userAgent ?? $_SERVER['HTTP_USER_AGENT'] ?? '';
        $lowerAgent = strtolower($this->userAgent);

        // Detect robot first
        $this->isRobot = $this->detectRobot($lowerAgent);

        // Detect platform
        $this->platform = $this->detectPlatform($lowerAgent);

        // Detect mobile/tablet from platform
        $this->isMobile = $this->detectMobile($lowerAgent, $this->platform);
        $this->isTablet = $this->detectTablet($lowerAgent, $this->platform);

        // Detect browser and version
        [$this->browser, $this->majorVersion, $this->minorVersion] =
            $this->detectBrowser($this->userAgent, $lowerAgent);

        // Initialize features and quirks
        $this->features = $this->detectFeatures($accept);
        $this->quirks = $this->detectQuirks();
    }

    /**
     * Detect browser family and version.
     *
     * @return array{BrowserFamily, int, int} [browser, major, minor]
     */
    private function detectBrowser(string $agent, string $lowerAgent): array
    {
        // Edge (must check before Chrome)
        if (preg_match('/edg(?:e|ios|a)?\/(\d+)\.(\d+)/i', $agent, $matches)) {
            return [BrowserFamily::Edge, (int)$matches[1], (int)$matches[2]];
        }

        // Chrome (must check before Safari)
        if (preg_match('/chrome\/(\d+)\.(\d+)/i', $agent, $matches)) {
            // Opera uses Chrome engine
            if (str_contains($lowerAgent, 'opr/') || str_contains($lowerAgent, 'opera')) {
                if (preg_match('/opr\/(\d+)\.(\d+)/i', $agent, $opMatches)) {
                    return [BrowserFamily::Opera, (int)$opMatches[1], (int)$opMatches[2]];
                }
                return [BrowserFamily::Opera, (int)$matches[1], (int)$matches[2]];
            }
            return [BrowserFamily::Chrome, (int)$matches[1], (int)$matches[2]];
        }

        // Safari
        if (preg_match('/version\/(\d+)\.(\d+)/i', $agent, $matches) &&
            str_contains($lowerAgent, 'safari')) {
            return [BrowserFamily::Safari, (int)$matches[1], (int)$matches[2]];
        }

        // Firefox
        if (preg_match('/firefox\/(\d+)\.(\d+)/i', $agent, $matches)) {
            return [BrowserFamily::Firefox, (int)$matches[1], (int)$matches[2]];
        }

        // Internet Explorer
        if (preg_match('/msie\s+(\d+)\.(\d+)/i', $agent, $matches) ||
            preg_match('/trident\/.*rv:(\d+)\.(\d+)/i', $agent, $matches)) {
            return [BrowserFamily::InternetExplorer, (int)$matches[1], (int)$matches[2]];
        }

        // Opera old versions
        if (preg_match('/opera[\/\s](\d+)\.(\d+)/i', $agent, $matches)) {
            return [BrowserFamily::Opera, (int)$matches[1], (int)$matches[2]];
        }

        return [BrowserFamily::Unknown, 0, 0];
    }

    /**
     * Detect platform from user agent.
     */
    private function detectPlatform(string $lowerAgent): Platform
    {
        // Mobile platforms first (more specific)
        if (str_contains($lowerAgent, 'ipad')) {
            return Platform::IPad;
        }
        if (str_contains($lowerAgent, 'iphone') || str_contains($lowerAgent, 'ipod')) {
            return Platform::IPhone;
        }
        if (str_contains($lowerAgent, 'android')) {
            return Platform::Android;
        }

        // Desktop platforms
        if (str_contains($lowerAgent, 'cros')) {
            return Platform::ChromeOS;
        }
        if (str_contains($lowerAgent, 'windows') || str_contains($lowerAgent, 'win32') ||
            str_contains($lowerAgent, 'win64')) {
            return Platform::Windows;
        }
        if (str_contains($lowerAgent, 'macintosh') || str_contains($lowerAgent, 'mac os x')) {
            return Platform::MacOS;
        }
        if (str_contains($lowerAgent, 'linux') || str_contains($lowerAgent, 'x11')) {
            return Platform::Linux;
        }

        return Platform::Unknown;
    }

    /**
     * Detect if device is mobile.
     */
    private function detectMobile(string $lowerAgent, Platform $platform): bool
    {
        // Platform-based detection
        if ($platform->isMobile()) {
            return true;
        }

        // Additional mobile indicators
        return str_contains($lowerAgent, 'mobile') ||
               str_contains($lowerAgent, 'iemobile') ||
               str_contains($lowerAgent, 'windows phone');
    }

    /**
     * Detect if device is tablet.
     */
    private function detectTablet(string $lowerAgent, Platform $platform): bool
    {
        // Platform-based detection
        if ($platform->isTablet()) {
            return true;
        }

        // Android tablets (no "Mobile" in UA, but has "Android")
        if ($platform === Platform::Android && !str_contains($lowerAgent, 'mobile')) {
            return true;
        }

        // Additional tablet indicators
        return str_contains($lowerAgent, 'tablet');
    }

    /**
     * Detect if user agent is a robot/crawler.
     */
    private function detectRobot(string $lowerAgent): bool
    {
        $robotPatterns = [
            'bot', 'crawler', 'spider', 'slurp', 'yahoo',
            'google', 'mediapartners', 'archive', 'ia_archiver',
            'baiduspider', 'yandex', 'duckduckbot', 'facebookexternalhit',
            'twitterbot', 'linkedinbot', 'whatsapp', 'slackbot',
            'telegrambot', 'applebot', 'petalbot', 'semrushbot',
        ];

        foreach ($robotPatterns as $pattern) {
            if (str_contains($lowerAgent, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect browser features.
     *
     * @return array<string, mixed>
     */
    private function detectFeatures(?string $accept): array
    {
        $features = [
            'javascript' => true,  // Modern browsers all support JS
            'ajax' => true,        // Modern browsers all support AJAX
            'dom' => true,         // Modern browsers all support DOM
            'xmlhttpreq' => true,  // Modern browsers all support XMLHttpRequest
        ];

        // UTF-8 support
        if (isset($_SERVER['HTTP_ACCEPT_CHARSET'])) {
            $features['utf'] = str_contains(
                strtolower($_SERVER['HTTP_ACCEPT_CHARSET']),
                'utf'
            );
        }

        return $features;
    }

    /**
     * Detect browser quirks.
     *
     * @return array<string, mixed>
     */
    private function detectQuirks(): array
    {
        $quirks = [];

        // Mobile quirks
        if ($this->isMobile) {
            $quirks['avoid_popup_windows'] = true;
        }

        // IE quirks
        if ($this->browser === BrowserFamily::InternetExplorer) {
            $quirks['no_filename_spaces'] = true;
        }

        return $quirks;
    }

    /**
     * Get browser family.
     */
    public function getBrowser(): BrowserFamily
    {
        return $this->browser;
    }

    /**
     * Get browser family as string (for BC compatibility).
     */
    public function getBrowserName(): string
    {
        return $this->browser->value;
    }

    /**
     * Check if browser matches given family.
     */
    public function isBrowser(BrowserFamily|string $browser): bool
    {
        if (is_string($browser)) {
            return $this->browser->value === $browser;
        }
        return $this->browser === $browser;
    }

    /**
     * Get major version number.
     */
    public function getMajorVersion(): int
    {
        return $this->majorVersion;
    }

    /**
     * Get minor version number.
     */
    public function getMinorVersion(): int
    {
        return $this->minorVersion;
    }

    /**
     * Get full version string.
     */
    public function getVersion(): string
    {
        return "{$this->majorVersion}.{$this->minorVersion}";
    }

    /**
     * Get platform.
     */
    public function getPlatform(): Platform
    {
        return $this->platform;
    }

    /**
     * Get platform as string (for BC compatibility).
     */
    public function getPlatformName(): string
    {
        return $this->platform->value;
    }

    /**
     * Check if device is mobile.
     */
    public function mobile(): bool
    {
        return $this->isMobile;
    }

    /**
     * Check if device is tablet.
     */
    public function tablet(): bool
    {
        return $this->isTablet;
    }

    /**
     * Check if user agent is robot/crawler.
     */
    public function robot(): bool
    {
        return $this->isRobot;
    }

    /**
     * Get user agent string.
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * Check if browser has a feature.
     */
    public function hasFeature(string $feature): bool
    {
        return isset($this->features[$feature]);
    }

    /**
     * Get feature value.
     */
    public function getFeature(string $feature): mixed
    {
        return $this->features[$feature] ?? null;
    }

    /**
     * Check if browser has a quirk.
     */
    public function hasQuirk(string $quirk): bool
    {
        return isset($this->quirks[$quirk]);
    }

    /**
     * Get quirk value.
     */
    public function getQuirk(string $quirk): mixed
    {
        return $this->quirks[$quirk] ?? null;
    }
}
