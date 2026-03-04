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
 * Platform enumeration.
 *
 * @category Horde
 * @package  Browser
 */
enum Platform: string
{
    case Windows = 'windows';
    case MacOS = 'macos';
    case Linux = 'linux';
    case IPhone = 'iphone';
    case IPad = 'ipad';
    case Android = 'android';
    case ChromeOS = 'chromeos';
    case Unknown = 'unknown';

    /**
     * Get display name for platform.
     */
    public function displayName(): string
    {
        return match ($this) {
            self::Windows => 'Windows',
            self::MacOS => 'macOS',
            self::Linux => 'Linux',
            self::IPhone => 'iPhone',
            self::IPad => 'iPad',
            self::Android => 'Android',
            self::ChromeOS => 'ChromeOS',
            self::Unknown => 'Unknown',
        };
    }

    /**
     * Check if platform is mobile.
     */
    public function isMobile(): bool
    {
        return match ($this) {
            self::IPhone, self::Android => true,
            default => false,
        };
    }

    /**
     * Check if platform is tablet.
     */
    public function isTablet(): bool
    {
        return match ($this) {
            self::IPad => true,
            default => false,
        };
    }

    /**
     * Check if platform is desktop.
     */
    public function isDesktop(): bool
    {
        return match ($this) {
            self::Windows, self::MacOS, self::Linux, self::ChromeOS => true,
            default => false,
        };
    }
}
