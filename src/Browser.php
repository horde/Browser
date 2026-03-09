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
 * ## Native vs Legacy Values
 *
 * This modern implementation returns **native/accurate values**,
 * which differ from the legacy `Horde_Browser` class:
 *
 * ### Browser Detection (Native)
 *
 * **Modern API returns actual browsers:**
 * ```php
 * $browser->getBrowser()     // BrowserFamily::Chrome (enum)
 * $browser->getBrowserName() // 'chrome' (string)
 * ```
 *
 * **Legacy API mapped everything to generic names:**
 * ```php
 * // Legacy Horde_Browser returned:
 * 'webkit'  // For Chrome, Safari, Edge (not distinguished!)
 * 'mozilla' // For Firefox (not 'firefox')
 * ```
 *
 * ### Platform Detection (Native)
 *
 * **Modern API distinguishes mobile platforms:**
 * ```php
 * $browser->getPlatform()     // Platform::IPhone (enum)
 * $browser->getPlatformName() // 'iphone' (string)
 * ```
 *
 * **Legacy API didn't distinguish:**
 * ```php
 * // Legacy Horde_Browser returned:
 * 'mac'  // For iPhone, iPad, macOS (not distinguished!)
 * 'unix' // For Android, Linux (not distinguished!)
 * 'win'  // For Windows (not 'windows')
 * ```
 *
 * ### Version Numbers (Native)
 *
 * **Modern API returns integers for comparison:**
 * ```php
 * $browser->getMajorVersion() // 120 (int)
 * $browser->getMinorVersion() // 5 (int)
 * if ($browser->getMajorVersion() >= 120) { ... } // Works!
 * ```
 *
 * **Legacy API returned strings:**
 * ```php
 * // Legacy Horde_Browser returned:
 * '120'      // Major as string (can't compare numerically)
 * '5.6543'   // Minor as string with extra decimals
 * ```
 *
 * ### Tablet Detection (Native, Fixed!)
 *
 * **Modern API properly detects tablets:**
 * ```php
 * $browser->tablet() // true for iPad, Android tablets
 * ```
 *
 * **Legacy API was broken:**
 * ```php
 * // Legacy Horde_Browser returned:
 * false // For iPad (broken detection!)
 * false // For Android tablets (broken detection!)
 * ```
 *
 * ## Backward Compatibility
 *
 * For code using the legacy `Horde_Browser` class, use the wrapper
 * in `lib/Horde/Browser.php` which:
 * - Delegates to this modern implementation
 * - Maps values back to legacy format
 * - Maintains full API compatibility
 * - Still benefits from improved detection underneath
 *
 * ## Usage Examples
 *
 * ```php
 * // Modern API (recommended)
 * $browser = new \Horde\Browser\Browser();
 *
 * // Browser detection with enums
 * if ($browser->getBrowser() === BrowserFamily::Chrome) {
 *     // Chrome-specific code
 * }
 *
 * // Platform detection with enums
 * if ($browser->getPlatform() === Platform::IPhone) {
 *     // iPhone-specific code
 * }
 *
 * // Version comparison (works correctly!)
 * if ($browser->getMajorVersion() >= 120) {
 *     // Modern browser features
 * }
 *
 * // Device type detection
 * if ($browser->mobile()) {
 *     // Mobile phone UI
 * } elseif ($browser->tablet()) {
 *     // Tablet UI (now works on iPad!)
 * } else {
 *     // Desktop UI
 * }
 *
 * // String values for convenience
 * $name = $browser->getBrowserName();    // 'chrome'
 * $platform = $browser->getPlatformName(); // 'iphone'
 * ```
 *
 * @category Horde
 * @package  Browser
 * @see BrowserFamily For browser types
 * @see Platform For platform types with helper methods
 */
class Browser
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
     * HTTP Accept header.
     */
    private string $accept;

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
        $this->accept = strtolower($accept ?? $_SERVER['HTTP_ACCEPT'] ?? '');
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
        // Default features for modern browsers
        $features = [
            'frames' => true,
            'html' => true,
            'images' => true,
            'java' => true,
            'javascript' => true,
            'tables' => true,
            'css' => true,
            'dom' => true,
            'iframes' => true,
            'accesskey' => true,
            'ajax' => false,  // Set per browser
            'xmlhttpreq' => true,
            'rte' => false,  // Rich text editor - set per browser
            'homepage' => false,  // Set per browser
            'optgroup' => false,  // Set per browser
            'cite' => false,  // Set per browser
        ];

        // Browser-specific feature detection
        $major = $this->majorVersion;
        $minor = $this->minorVersion;

        switch ($this->browser) {
            case BrowserFamily::InternetExplorer:
                // IE 5 Mac had limited features
                if ($major == 5 && str_contains(strtolower($this->userAgent), 'mac')) {
                    $features['javascript'] = '1.2';
                    $features['ajax'] = false;
                    $features['dataurl'] = false;
                    $features['rte'] = false;
                    $features['accesskey'] = true;
                    $features['homepage'] = true;
                }
                // IE 5 Windows
                elseif ($major == 5) {
                    $features['javascript'] = '1.4';
                    $features['dom'] = true;
                    $features['ajax'] = false;
                    $features['rte'] = true;
                    $features['accesskey'] = true;
                    $features['homepage'] = true;
                    if ($minor == 5) {
                        // IE 5.5 specific
                    }
                }
                // IE 6
                elseif ($major == 6) {
                    $features['javascript'] = '1.4';
                    $features['dom'] = true;
                    $features['ajax'] = false;
                    $features['rte'] = true;
                    $features['accesskey'] = true;
                    $features['homepage'] = true;
                    $features['optgroup'] = true;
                }
                // IE 7
                elseif ($major == 7) {
                    $features['javascript'] = '1.4';
                    $features['ajax'] = true;  // AJAX added in IE7
                    $features['dom'] = true;
                    $features['rte'] = true;
                    $features['accesskey'] = true;
                    $features['homepage'] = true;
                    $features['optgroup'] = true;
                    $features['dataurl'] = false;
                }
                // IE 8
                elseif ($major == 8) {
                    $features['javascript'] = '1.4';
                    $features['ajax'] = true;
                    $features['dom'] = true;
                    $features['rte'] = true;
                    $features['accesskey'] = true;
                    $features['homepage'] = true;
                    $features['optgroup'] = true;
                    $features['dataurl'] = 32768;  // 32KB limit
                }
                // IE 9+
                elseif ($major >= 9) {
                    $features['javascript'] = '1.4';
                    $features['ajax'] = true;
                    $features['dom'] = true;
                    $features['rte'] = true;
                    $features['accesskey'] = true;
                    $features['homepage'] = true;
                    $features['optgroup'] = true;
                    $features['cite'] = true;
                    $features['dataurl'] = true;
                }
                break;

            case BrowserFamily::Opera:
                // Opera 6
                if ($major == 6) {
                    $features['javascript'] = '1.5';
                    $features['ajax'] = false;
                    $features['dom'] = false;
                    $features['iframes'] = true;
                    $features['accesskey'] = true;
                }
                // Opera 7
                elseif ($major == 7) {
                    $features['javascript'] = '1.5';
                    $features['ajax'] = false;
                    $features['dom'] = true;
                    $features['iframes'] = true;
                    $features['accesskey'] = true;
                }
                // Opera 9+
                elseif ($major >= 9) {
                    $features['javascript'] = '1.5';
                    $features['ajax'] = true;  // AJAX added in Opera 9
                    $features['dom'] = true;
                    $features['iframes'] = true;
                    $features['accesskey'] = true;
                    $features['dataurl'] = 4100;  // 4KB limit
                }
                break;

            case BrowserFamily::Firefox:
                $features['ajax'] = true;
                $features['rte'] = true;
                $features['accesskey'] = true;
                $features['cite'] = true;
                $features['optgroup'] = true;
                break;

            case BrowserFamily::Chrome:
                $features['ajax'] = true;
                $features['rte'] = true;
                $features['accesskey'] = true;
                break;

            case BrowserFamily::Safari:
                $features['ajax'] = true;
                $features['rte'] = true;
                // Safari 1.3+ has accesskey
                if ($major > 1 || ($major == 1 && $minor >= 3)) {
                    $features['accesskey'] = true;
                }
                break;

            case BrowserFamily::Edge:
                $features['ajax'] = true;
                $features['rte'] = true;
                $features['accesskey'] = true;
                break;
        }

        // Mobile browser constraints
        $lowerAgent = strtolower($this->userAgent);

        // Windows Phone 6-7
        if (str_contains($lowerAgent, 'windows phone os') &&
            preg_match('/windows phone os ([67])/', $lowerAgent)) {
            $features['frames'] = false;
            $features['javascript'] = false;
        }

        // Opera Mini
        if (str_contains($lowerAgent, 'opera mini')) {
            $features['frames'] = false;
            // JavaScript may be enabled/disabled in Opera Mini
        }

        // UTF-8 support from Accept-Charset header
        if (isset($_SERVER['HTTP_ACCEPT_CHARSET'])) {
            $features['utf'] = str_contains(
                strtolower($_SERVER['HTTP_ACCEPT_CHARSET']),
                'utf'
            );
        } else {
            $features['utf'] = false;
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
            $major = $this->majorVersion;
            $minor = $this->minorVersion;
            $lowerAgent = strtolower($this->userAgent);

            // All IE versions have these quirks
            $quirks['cache_ssl_downloads'] = true;
            $quirks['cache_same_url'] = true;
            $quirks['break_disposition_filename'] = true;
            $quirks['no_filename_spaces'] = true;

            // IE 5.5 specific
            if ($major == 5 && $minor == 5) {
                $quirks['break_disposition_header'] = true;
            }

            // IE < 7 on Windows has PNG transparency issues
            if ($major < 7 && str_contains($lowerAgent, 'windows')) {
                $quirks['png_transparency'] = true;
            }

            // IE 6 specific quirks
            if ($major == 6) {
                $quirks['scrollbar_in_way'] = true;
                $quirks['broken_multipart_form'] = true;
                $quirks['windowed_controls'] = true;
            }

            // IE 5 quirks
            if ($major == 5) {
                $quirks['broken_multipart_form'] = true;
                $quirks['windowed_controls'] = true;
            }
        }

        // Opera quirks
        if ($this->browser === BrowserFamily::Opera) {
            $major = $this->majorVersion;

            // All Opera versions
            $quirks['no_filename_spaces'] = true;

            // Opera >= 7
            if ($major >= 7) {
                $quirks['double_linebreak_textarea'] = true;
            }
        }

        return $quirks;
    }

    /**
     * Get browser family (native).
     *
     * Returns the actual detected browser family as an enum.
     *
     * **Native values** (modern API):
     * - Chrome → BrowserFamily::Chrome
     * - Safari → BrowserFamily::Safari
     * - Edge → BrowserFamily::Edge
     * - Firefox → BrowserFamily::Firefox
     * - Opera → BrowserFamily::Opera
     *
     * **Contrast with legacy Horde_Browser:**
     * - Legacy returns 'webkit' for Chrome/Safari/Edge
     * - Legacy returns 'mozilla' for Firefox
     * - This method returns the actual browser family
     *
     * @return BrowserFamily Browser family enum
     * @see getBrowserName() For string representation
     * @see BrowserFamily For all possible values
     */
    public function getBrowser(): BrowserFamily
    {
        return $this->browser;
    }

    /**
     * Get browser family as string (convenience method).
     *
     * Returns the enum value as a string for convenience.
     * This is NOT a legacy-compatible value.
     *
     * **Returns native string values**:
     * - 'chrome' (not 'webkit')
     * - 'safari' (not 'webkit')
     * - 'edge' (not 'webkit')
     * - 'firefox' (not 'mozilla')
     * - 'opera', 'ie', 'unknown'
     *
     * **For legacy compatibility**, use the wrapper class `Horde_Browser`
     * which maps these to legacy values like 'webkit' and 'mozilla'.
     *
     * @return string Browser family name
     * @see getBrowser() For enum representation
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
     * Get major version number (native).
     *
     * Returns the major version as an integer for proper version comparison.
     *
     * **Native behavior** (modern API):
     * - Returns integer: 120
     * - Chrome 120.0.0.0 → 120 (int)
     * - Firefox 121.0 → 121 (int)
     *
     * **Contrast with legacy Horde_Browser:**
     * - Legacy returns string: '120'
     * - Cannot do proper numeric comparisons
     * - This method returns actual integer
     *
     * @return int Major version number
     * @see getMinorVersion() For minor version
     * @see getVersion() For full version string
     */
    public function getMajorVersion(): int
    {
        return $this->majorVersion;
    }

    /**
     * Get minor version number (native).
     *
     * Returns the minor version as an integer for proper version comparison.
     *
     * **Native behavior** (modern API):
     * - Returns integer: 5
     * - Chrome 120.5 → 5 (int)
     * - Clean integer value
     *
     * **Contrast with legacy Horde_Browser:**
     * - Legacy sometimes returns strings like '5.6543'
     * - Mixed types (sometimes int, sometimes string)
     * - This method returns clean integer
     *
     * @return int Minor version number
     * @see getMajorVersion() For major version
     * @see getVersion() For full version string
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
     * Get platform (native).
     *
     * Returns the actual detected platform as an enum.
     *
     * **Native values** (modern API):
     * - iPhone → Platform::IPhone
     * - iPad → Platform::IPad
     * - Android → Platform::Android
     * - Windows → Platform::Windows
     * - macOS → Platform::MacOS
     * - Linux → Platform::Linux
     *
     * **Contrast with legacy Horde_Browser:**
     * - Legacy returns 'mac' for iPhone/iPad (not distinguished!)
     * - Legacy returns 'unix' for Android (not distinguished!)
     * - Legacy returns 'win' instead of 'windows'
     * - This method returns the actual platform
     *
     * @return Platform Platform enum
     * @see getPlatformName() For string representation
     * @see Platform For all possible values and helper methods
     */
    public function getPlatform(): Platform
    {
        return $this->platform;
    }

    /**
     * Get platform as string (convenience method).
     *
     * Returns the enum value as a string for convenience.
     * This is NOT a legacy-compatible value.
     *
     * **Returns native string values**:
     * - 'iphone' (not 'mac')
     * - 'ipad' (not 'mac')
     * - 'android' (not 'unix')
     * - 'windows' (not 'win')
     * - 'macos' (not 'mac')
     * - 'linux' (not 'unix')
     * - 'chromeos', 'unknown'
     *
     * **For legacy compatibility**, use the wrapper class `Horde_Browser`
     * which maps these to legacy values like 'mac' and 'unix'.
     *
     * @return string Platform name
     * @see getPlatform() For enum representation
     */
    public function getPlatformName(): string
    {
        return $this->platform->value;
    }

    /**
     * Check if device is mobile (native).
     *
     * Returns true if device is a mobile phone.
     *
     * **Native behavior** (modern API):
     * - iPhone → true
     * - Android phone → true
     * - iPad → false (use tablet() instead)
     * - Android tablet → false (use tablet() instead)
     * - Desktop → false
     *
     * **Contrast with legacy Horde_Browser:**
     * - Legacy sometimes returns true for tablets
     * - This method distinguishes mobile from tablet
     *
     * @return bool True if mobile phone
     * @see tablet() To check if device is a tablet
     * @see Platform::isMobile() For platform-based check
     */
    public function mobile(): bool
    {
        return $this->isMobile;
    }

    /**
     * Check if device is tablet (native, improved detection).
     *
     * Returns true if device is a tablet.
     *
     * **Native behavior** (modern API):
     * - iPad → true (detected correctly!)
     * - Android tablets → true (detected correctly!)
     * - Mobile phones → false
     * - Desktop → false
     *
     * **Contrast with legacy Horde_Browser:**
     * - Legacy returns false for iPad (broken!)
     * - Legacy returns false for Android tablets (broken!)
     * - This method properly detects tablets
     *
     * **Detection logic:**
     * - iPad: Detected from user agent "iPad"
     * - Android tablets: No "Mobile" in UA but has "Android"
     *
     * @return bool True if tablet
     * @see mobile() To check if device is a mobile phone
     * @see Platform::isTablet() For platform-based check
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
     * Check if browser version is unsupported for modern features.
     *
     * Returns true if the browser is a mainstream browser in an ancient version
     * that cannot support modern CSS3 (Grid, Flexbox, Variables) and ES6+ JavaScript
     * required by Horde's responsive UI.
     *
     * Based on minimum requirements:
     * - CSS Grid (2017)
     * - CSS Variables (2016-2017)
     * - ES6 Modules (2017)
     * - Flexbox (2015)
     *
     * Minimum supported versions:
     * - Chrome: 61+ (September 2017)
     * - Firefox: 60+ (May 2018)
     * - Safari: 11+ (September 2017)
     * - Edge: 16+ (October 2017)
     * - Opera: 48+ (September 2017)
     * - IE: Not supported (any version)
     *
     * Note: Unknown or niche browsers (Lynx, custom UAs, bots) return false.
     * Only mainstream browsers in ancient versions are flagged as unsupported.
     *
     * @return bool True if browser version is too old for modern features
     */
    public function hasFeature(string $feature): bool
    {
        // Special feature: unsupported_browser_version
        if ($feature === 'unsupported_browser_version') {
            return $this->isUnsupportedBrowserVersion();
        }

        return !empty($this->features[$feature]);
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

    /**
     * Set a feature value.
     *
     * @param string $feature Feature name
     * @param mixed  $value   Feature value (true/false or specific value)
     */
    public function setFeature(string $feature, mixed $value = true): void
    {
        if ($value) {
            $this->features[$feature] = $value;
        } else {
            unset($this->features[$feature]);
        }
    }

    /**
     * Set a quirk value.
     *
     * @param string $quirk Quirk name
     * @param mixed  $value Quirk value (typically true/false)
     */
    public function setQuirk(string $quirk, mixed $value = true): void
    {
        if ($value) {
            $this->quirks[$quirk] = $value;
        } else {
            unset($this->quirks[$quirk]);
        }
    }

    /**
     * Check if MIME type is viewable.
     *
     * Determines if the browser can view a given MIME type based on:
     * 1. HTTP Accept header negotiation
     * 2. Browser feature detection
     * 3. Default viewable types allowlist
     *
     * @param string $mimetype MIME type to check (e.g., 'image/png')
     * @return bool True if viewable
     */
    public function isViewable(string $mimetype): bool
    {
        $mimetype = strtolower($mimetype);
        [$type, $subtype] = explode('/', $mimetype, 2) + ['', ''];

        // Check HTTP Accept header if present
        if (!empty($this->accept)) {
            $wildcard_match = false;

            // 1. Check exact MIME type match
            if (str_contains($this->accept, $mimetype)) {
                return true;
            }

            // 2. Check for wildcard match
            if (str_contains($this->accept, '*/*')) {
                $wildcard_match = true;
                // Non-image types are accepted with */*
                if ($type !== 'image') {
                    return true;
                }
            }

            // 3. Firefox pjpeg/jpeg compatibility quirk
            // Mozilla browsers treat image/pjpeg and image/jpeg as same
            if ($this->browser === BrowserFamily::Firefox &&
                $mimetype === 'image/pjpeg' &&
                str_contains($this->accept, 'image/jpeg')) {
                return true;
            }

            // 4. Non-image types with wildcard must be explicitly accepted
            if (!$wildcard_match) {
                return false;
            }

            // For images with wildcard, continue to feature check below
        }

        // If no Accept header or wildcard match for images, check features and allowlist

        // 5. For image types, check if browser has images feature
        if ($type === 'image') {
            if (!$this->hasFeature('images')) {
                return false;
            }

            // Check against allowlist of image subtypes
            $imageSubtypes = ['jpeg', 'gif', 'png', 'pjpeg', 'x-png', 'bmp', 'webp', 'svg+xml'];
            return in_array($subtype, $imageSubtypes, true);
        }

        // For non-image types without Accept header, use default allowlist
        $viewable = [
            'text/plain',
            'text/html',
            'text/xml',
            'application/pdf',
            'video/mp4',
            'video/webm',
            'audio/mpeg',
            'audio/ogg',
        ];

        return in_array($mimetype, $viewable, true);
    }

    /**
     * Check if browser version is too old for modern features.
     *
     * Checks if the browser is a mainstream browser in an ancient version
     * that cannot support modern CSS3 and ES6+ JavaScript required by
     * Horde's responsive UI.
     *
     * Minimum supported versions (September 2017 baseline):
     * - Chrome: 61+
     * - Firefox: 60+
     * - Safari: 11+
     * - Edge: 16+
     * - Opera: 48+
     * - IE: Not supported (any version)
     *
     * Unknown/niche browsers return false (not flagged as unsupported).
     *
     * @return bool True if browser version is unsupported
     */
    private function isUnsupportedBrowserVersion(): bool
    {
        $major = $this->majorVersion;

        return match ($this->browser) {
            // Internet Explorer - all versions unsupported
            BrowserFamily::InternetExplorer => true,

            // Edge Legacy (EdgeHTML) < 16 unsupported
            // Edge Chromium 79+ is fine (same as Chrome)
            BrowserFamily::Edge => $major < 16,

            // Chrome < 61 unsupported (September 2017)
            BrowserFamily::Chrome => $major < 61,

            // Firefox < 60 unsupported (May 2018)
            BrowserFamily::Firefox => $major < 60,

            // Safari < 11 unsupported (September 2017)
            BrowserFamily::Safari => $major < 11,

            // Opera < 48 unsupported (September 2017)
            BrowserFamily::Opera => $major < 48,

            // Unknown browsers are NOT flagged as unsupported
            default => false,
        };
    }
}
