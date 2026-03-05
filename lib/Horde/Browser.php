<?php
/**
 * Copyright 1999-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL-2.1).
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jon Parise <jon@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package  Browser
 */

use Horde\Browser\Browser as ModernBrowser;
use Horde\Browser\BrowserFamily;
use Horde\Browser\Platform;

/**
 * Backward-compatible wrapper around modern Horde\Browser\Browser.
 *
 * This class provides full backward compatibility with legacy code while
 * delegating all browser detection to the modern implementation.
 *
 * ## Legacy Value Mapping
 *
 * This wrapper **maps modern values back to legacy format** to maintain
 * backward compatibility:
 *
 * ### Browser Names (Legacy Format)
 *
 * **Returns legacy generic names:**
 * ```php
 * $browser->getBrowser() // Returns legacy values:
 * 'webkit'  // For Chrome, Safari, Edge (not distinguished)
 * 'mozilla' // For Firefox (not 'firefox')
 * 'msie'    // For Internet Explorer
 * 'opera'   // For old Opera (pre-Chromium)
 * ```
 *
 * **Modern implementation detects:**
 * - Chrome → Mapped to 'webkit'
 * - Safari → Mapped to 'webkit'
 * - Edge → Mapped to 'webkit'
 * - Firefox → Mapped to 'mozilla'
 *
 * ### Platform Names (Legacy Format)
 *
 * **Returns legacy platform names:**
 * ```php
 * $browser->getPlatform() // Returns legacy values:
 * 'mac'  // For macOS, iPhone, iPad (not distinguished!)
 * 'unix' // For Linux, Android (not distinguished!)
 * 'win'  // For Windows
 * ```
 *
 * **Modern implementation detects:**
 * - iPhone → Mapped to 'mac'
 * - iPad → Mapped to 'mac'
 * - Android → Mapped to 'unix'
 * - macOS → Mapped to 'mac'
 * - Linux → Mapped to 'unix'
 * - Windows → Mapped to 'win'
 *
 * ### Version Numbers (Legacy Format)
 *
 * **Returns strings for backward compatibility:**
 * ```php
 * $browser->getMajor() // '120' (string, not int)
 * $browser->getMinor() // 5 (int - improved from legacy!)
 * ```
 *
 * **Legacy behavior maintained:**
 * - Major version as string (BC requirement)
 * - Minor version as integer (improvement!)
 *
 * ### Improvements Provided by Wrapper
 *
 * Even with legacy API, you get improvements:
 *
 * **1. Correct Firefox Version:**
 * ```php
 * // Firefox 121 user agent
 * $browser->getMajor() // '121' (actual Firefox version!)
 * // Legacy returned '5' (Mozilla version)
 * ```
 *
 * **2. Working Tablet Detection:**
 * ```php
 * $browser->isTablet() // true for iPad (fixed!)
 * // Legacy returned false (broken!)
 * ```
 *
 * **3. Clean Minor Versions:**
 * ```php
 * $browser->getMinor() // 5 (clean integer)
 * // Legacy returned '5.6543' (string with extra decimals)
 * ```
 *
 * **4. Better Robot Detection:**
 * - Modern patterns for new bots (DuckDuckBot, AppleBot, etc.)
 *
 * ## Migration to Modern API
 *
 * To use the modern API with native values:
 *
 * ```php
 * // Old (legacy wrapper)
 * $browser = new Horde_Browser();
 * $browser->getBrowser();    // 'webkit' (generic)
 * $browser->getMajor();      // '120' (string)
 * $browser->getPlatform();   // 'mac' (generic)
 *
 * // New (modern implementation)
 * $browser = new \Horde\Browser\Browser();
 * $browser->getBrowser();       // BrowserFamily::Chrome (specific!)
 * $browser->getMajorVersion();  // 120 (int for comparison!)
 * $browser->getPlatform();      // Platform::IPhone (specific!)
 * ```
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Jon Parise <jon@horde.org>
 * @category  Horde
 * @copyright 1999-2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Browser
 * @see \Horde\Browser\Browser For modern implementation with native values
 */
class Horde_Browser
{
    /**
     * Modern browser instance (does the real work).
     */
    protected ModernBrowser $_modern;

    /**
     * Manually set browser name (for backward compatibility).
     */
    protected ?string $_manualBrowser = null;

    /**
     * Manually set mobile flag (for backward compatibility).
     */
    protected ?bool $_manualMobile = null;

    /**
     * Manually set tablet flag (for backward compatibility).
     */
    protected ?bool $_manualTablet = null;

    /**
     * Custom features (for backward compatibility).
     *
     * @var array<string, mixed>
     */
    protected array $_customFeatures = [];

    /**
     * Custom quirks (for backward compatibility).
     *
     * @var array<string, mixed>
     */
    protected array $_customQuirks = [];

    /**
     * Create browser instance.
     *
     * @param string|null $userAgent User agent string
     * @param string|null $accept    HTTP Accept header
     */
    public function __construct($userAgent = null, $accept = null)
    {
        $this->match($userAgent, $accept);
    }

    /**
     * Parse user agent and initialize browser detection.
     *
     * @param string|null $userAgent User agent string
     * @param string|null $accept    HTTP Accept header
     * @return void
     */
    public function match($userAgent = null, $accept = null)
    {
        $this->_modern = new ModernBrowser($userAgent, $accept);
    }

    /**
     * Get browser name (legacy format).
     *
     * Returns legacy browser name for backward compatibility.
     *
     * **Legacy values returned:**
     * - `'webkit'` - For Chrome, Safari, Edge (not distinguished)
     * - `'mozilla'` - For Firefox
     * - `'msie'` - For Internet Explorer
     * - `'opera'` - For old Opera
     * - `''` - For unknown browsers
     *
     * **Modern implementation detects but maps to legacy:**
     * - Chrome → 'webkit' (mapped)
     * - Safari → 'webkit' (mapped)
     * - Edge → 'webkit' (mapped)
     * - Firefox → 'mozilla' (mapped)
     *
     * **For native values**, use `\Horde\Browser\Browser::getBrowser()`
     * which returns distinct `BrowserFamily` enum values.
     *
     * @return string Legacy browser name
     * @see \Horde\Browser\Browser::getBrowser() For native enum value
     * @see \Horde\Browser\Browser::getBrowserName() For native string value
     */
    public function getBrowser(): string
    {
        if ($this->_manualBrowser !== null) {
            return $this->_manualBrowser;
        }

        // Map modern enum to legacy string values
        // Note: Modern Opera (Chromium-based) still detected as webkit for BC
        return match ($this->_modern->getBrowser()) {
            BrowserFamily::Chrome => 'webkit',           // Legacy compat
            BrowserFamily::Safari => 'webkit',           // Legacy compat
            BrowserFamily::Edge => 'webkit',             // Legacy compat
            BrowserFamily::Opera => 'webkit',            // Modern Opera (Chromium) - Legacy compat
            BrowserFamily::Firefox => 'mozilla',         // Legacy compat
            BrowserFamily::InternetExplorer => 'msie',
            BrowserFamily::Unknown => '',
        };
    }

    /**
     * Check if browser matches given name.
     *
     * @param string $browser Browser name to check
     * @return bool True if matches
     */
    public function isBrowser($browser): bool
    {
        return $this->getBrowser() === $browser;
    }

    /**
     * Manually set browser name.
     *
     * @param string $browser Browser name
     * @return void
     */
    public function setBrowser($browser)
    {
        $this->_manualBrowser = $browser;
    }

    /**
     * Get major version (legacy string format).
     *
     * Returns major version as string for backward compatibility.
     *
     * **Returns:** String like `'120'`, `'121'`, etc.
     *
     * **Improvement over pure legacy:**
     * - Firefox now returns actual version ('121') not Mozilla version ('5')
     * - Still returns string for BC, but correct version number
     *
     * **For native integer value**, use `\Horde\Browser\Browser::getMajorVersion()`
     * which returns int for proper numeric comparison.
     *
     * @return string Major version as string
     * @see \Horde\Browser\Browser::getMajorVersion() For native integer value
     */
    public function getMajor(): string
    {
        return (string)$this->_modern->getMajorVersion();
    }

    /**
     * Get minor version (improved format).
     *
     * Returns minor version as integer (improvement over legacy).
     *
     * **Returns:** Integer like `5`, `0`, etc.
     *
     * **Improvement over pure legacy:**
     * - Returns clean integer (5) not string ('5.6543')
     * - Consistent type (always int)
     *
     * **Same as modern API**: `\Horde\Browser\Browser::getMinorVersion()`
     *
     * @return string|int Minor version (usually int, for BC may be mixed)
     * @see \Horde\Browser\Browser::getMinorVersion() For guaranteed integer
     */
    public function getMinor()
    {
        $minor = $this->_modern->getMinorVersion();

        // Legacy sometimes returned strings like "0.0.0"
        // Modern returns clean integer
        return $minor;
    }

    /**
     * Get full version string.
     *
     * @return string Version string
     */
    public function getVersion(): string
    {
        $major = $this->getMajor();
        $minor = $this->getMinor();

        if ($minor === 0) {
            return $major;
        }

        return "{$major}.{$minor}";
    }

    /**
     * Get platform (legacy format).
     *
     * Returns legacy platform name for backward compatibility.
     *
     * **Legacy values returned:**
     * - `'mac'` - For macOS, iPhone, iPad (not distinguished!)
     * - `'unix'` - For Linux, Android (not distinguished!)
     * - `'win'` - For Windows
     * - `''` - For unknown platforms
     *
     * **Modern implementation detects but maps to legacy:**
     * - iPhone → 'mac' (mapped, not distinguished)
     * - iPad → 'mac' (mapped, not distinguished)
     * - Android → 'unix' (mapped, not distinguished)
     * - macOS → 'mac'
     * - Linux → 'unix'
     * - Windows → 'win'
     *
     * **For native values**, use `\Horde\Browser\Browser::getPlatform()`
     * which returns distinct `Platform` enum values like `Platform::IPhone`.
     *
     * @return string Legacy platform name
     * @see \Horde\Browser\Browser::getPlatform() For native enum value
     * @see \Horde\Browser\Browser::getPlatformName() For native string value
     */
    public function getPlatform(): string
    {
        // Map modern enum to legacy string values
        return match ($this->_modern->getPlatform()) {
            Platform::Windows => 'win',
            Platform::MacOS => 'mac',
            Platform::Linux => 'unix',
            Platform::IPhone => 'mac',        // Legacy compat (was not distinguished)
            Platform::IPad => 'mac',          // Legacy compat (was not distinguished)
            Platform::Android => 'unix',      // Legacy compat (was not distinguished)
            Platform::ChromeOS => 'unix',
            Platform::Unknown => '',
        };
    }

    /**
     * Check if device is mobile (improved detection).
     *
     * Returns true if device is a mobile phone or tablet.
     *
     * **Delegates to modern implementation** with improved detection.
     *
     * **Returns true for:**
     * - iPhone
     * - iPad (BC: tablets are considered "mobile" in legacy API)
     * - Android phones
     * - Android tablets (BC: tablets are considered "mobile" in legacy API)
     * - Windows Phone
     *
     * **Returns false for:**
     * - Desktop computers
     *
     * **Note:** Legacy API did not distinguish tablets from mobile phones.
     * Both return true for isMobile(). Use isTablet() to distinguish.
     *
     * **Can be overridden** with setMobile() for custom detection.
     *
     * @return bool True if mobile phone or tablet
     * @see isTablet() To check specifically for tablets
     * @see setMobile() To manually override detection
     */
    public function isMobile(): bool
    {
        if ($this->_manualMobile !== null) {
            return $this->_manualMobile;
        }

        // Legacy BC: tablets are considered "mobile"
        return $this->_modern->isMobile || $this->_modern->isTablet;
    }

    /**
     * Manually set mobile flag.
     *
     * Override automatic mobile detection with custom value.
     *
     * @param bool $mobile Mobile flag
     * @return void
     */
    public function setMobile($mobile)
    {
        $this->_manualMobile = (bool)$mobile;
    }

    /**
     * Check if device is tablet (improved detection!).
     *
     * Returns true if device is a tablet.
     *
     * **Improvement over pure legacy:**
     * - iPad now correctly detected as tablet (was broken!)
     * - Android tablets now correctly detected (was broken!)
     *
     * **Returns true for:**
     * - iPad
     * - Android tablets
     *
     * **Returns false for:**
     * - Mobile phones
     * - Desktop computers
     *
     * **Can be overridden** with setTablet() for custom detection.
     *
     * @return bool True if tablet
     * @see isMobile() To check for mobile phones
     * @see setTablet() To manually override detection
     * @see \Horde\Browser\Browser::tablet() For same behavior
     */
    public function isTablet(): bool
    {
        if ($this->_manualTablet !== null) {
            return $this->_manualTablet;
        }

        return $this->_modern->isTablet;
    }

    /**
     * Manually set tablet flag.
     *
     * Override automatic tablet detection with custom value.
     *
     * @param bool $tablet Tablet flag
     * @return void
     */
    public function setTablet($tablet)
    {
        $this->_manualTablet = (bool)$tablet;
    }

    /**
     * Check if user agent is a robot.
     *
     * @return bool True if robot
     */
    public function isRobot(): bool
    {
        return $this->_modern->isRobot;
    }

    /**
     * Get user agent string.
     *
     * @return string User agent
     */
    public function getAgentString(): string
    {
        return $this->_modern->userAgent;
    }

    /**
     * Check if browser has a feature.
     *
     * @param string $feature Feature name
     * @return bool True if has feature
     */
    public function hasFeature($feature): bool
    {
        if (isset($this->_customFeatures[$feature])) {
            return true;
        }

        return $this->_modern->hasFeature($feature);
    }

    /**
     * Get feature value.
     *
     * @param string $feature Feature name
     * @return mixed Feature value
     */
    public function getFeature($feature)
    {
        if (isset($this->_customFeatures[$feature])) {
            return $this->_customFeatures[$feature];
        }

        return $this->_modern->getFeature($feature);
    }

    /**
     * Set a feature.
     *
     * @param string $feature Feature name
     * @param mixed $value Feature value
     * @return void
     */
    public function setFeature($feature, $value = true)
    {
        $this->_customFeatures[$feature] = $value;
    }

    /**
     * Check if browser has a quirk.
     *
     * @param string $quirk Quirk name
     * @return bool True if has quirk
     */
    public function hasQuirk($quirk): bool
    {
        if (isset($this->_customQuirks[$quirk])) {
            return true;
        }

        return $this->_modern->hasQuirk($quirk);
    }

    /**
     * Get quirk value.
     *
     * @param string $quirk Quirk name
     * @return mixed Quirk value
     */
    public function getQuirk($quirk)
    {
        if (isset($this->_customQuirks[$quirk])) {
            return $this->_customQuirks[$quirk];
        }

        return $this->_modern->getQuirk($quirk);
    }

    /**
     * Set a quirk.
     *
     * @param string $quirk Quirk name
     * @param mixed $value Quirk value
     * @return void
     */
    public function setQuirk($quirk, $value = true)
    {
        $this->_customQuirks[$quirk] = $value;
    }

    /**
     * Check if using SSL connection.
     *
     * @return bool True if using SSL
     */
    public function usingSSLConnection(): bool
    {
        return ((isset($_SERVER['HTTPS']) &&
                 ($_SERVER['HTTPS'] == 'on')) ||
                getenv('SSL_PROTOCOL_VERSION'));
    }

    /**
     * Get HTTP protocol version.
     *
     * @return string|null HTTP protocol version
     */
    public function getHTTPProtocol(): ?string
    {
        return (isset($_SERVER['SERVER_PROTOCOL']) && ($pos = strrpos($_SERVER['SERVER_PROTOCOL'], '/')))
            ? substr($_SERVER['SERVER_PROTOCOL'], $pos + 1)
            : null;
    }

    /**
     * Get client IP address.
     *
     * @return string IP address
     */
    public function getIPAddress(): string
    {
        return empty($_SERVER['HTTP_X_FORWARDED_FOR'])
            ? ($_SERVER['REMOTE_ADDR'] ?? '')
            : $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    /**
     * Check if file uploads are allowed.
     *
     * @return int Maximum upload size in bytes, or 0 if not allowed
     */
    public static function allowFileUploads(): int
    {
        if (!ini_get('file_uploads') ||
            (($dir = ini_get('upload_tmp_dir')) &&
             !is_writable($dir))) {
            return 0;
        }

        $filesize = self::_parseIniSize(ini_get('upload_max_filesize'));
        $postsize = self::_parseIniSize(ini_get('post_max_size'));

        return min($filesize, $postsize);
    }

    /**
     * Parse PHP ini size value (e.g., "8M" -> 8388608).
     *
     * @param string $size Size string
     * @return int Size in bytes
     */
    private static function _parseIniSize(string $size): int
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
     * Check if a file was uploaded.
     *
     * @param string $field Form field name
     * @param string|null $name Expected filename (unused, for BC)
     * @return bool True if file was uploaded
     */
    public function wasFileUploaded($field, $name = null): bool
    {
        return isset($_FILES[$field]) &&
               $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * Send download headers.
     *
     * @param string $filename Filename for download
     * @param string|null $ctype Content type
     * @param bool $inline Display inline (true) or as attachment (false)
     * @param string|null $cLength Content length
     * @return void
     */
    public function downloadHeaders(
        $filename,
        $ctype = null,
        $inline = false,
        $cLength = null
    ) {
        if ($ctype === null) {
            $ctype = 'application/octet-stream';
        }

        header('Content-Type: ' . trim($ctype));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        if ($cLength !== null) {
            header('Content-Length: ' . $cLength);
        }

        $disposition = $inline ? 'inline' : 'attachment';
        header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
    }

    /**
     * Check if MIME type is viewable in browser.
     *
     * @param string $mimetype MIME type
     * @return bool True if viewable
     */
    public function isViewable($mimetype): bool
    {
        $viewable = [
            'text/plain',
            'text/html',
            'text/xml',
            'image/gif',
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/svg+xml',
            'application/pdf',
            'video/mp4',
            'video/webm',
            'audio/mpeg',
            'audio/ogg',
        ];

        return in_array(strtolower($mimetype), $viewable, true);
    }
}
