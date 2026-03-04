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
 * Browser family enumeration.
 *
 * @category Horde
 * @package  Browser
 */
enum BrowserFamily: string
{
    case Chrome = 'chrome';
    case Firefox = 'firefox';
    case Safari = 'safari';
    case Edge = 'edge';
    case InternetExplorer = 'ie';
    case Opera = 'opera';
    case Unknown = 'unknown';

    /**
     * Get display name for browser family.
     */
    public function displayName(): string
    {
        return match ($this) {
            self::Chrome => 'Chrome',
            self::Firefox => 'Firefox',
            self::Safari => 'Safari',
            self::Edge => 'Microsoft Edge',
            self::InternetExplorer => 'Internet Explorer',
            self::Opera => 'Opera',
            self::Unknown => 'Unknown',
        };
    }
}
