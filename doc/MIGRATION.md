# Horde Browser 3.0.0 Migration Guide

**Version:** 3.0.0alpha4 → 3.0.0alpha8+
**Date:** 2026-03-09

---

## Breaking Changes

### 1. wasFileUploaded() Exception Behavior

**Change:** Method now throws exceptions for **all** error conditions (no boolean return).

**Before (alpha4):**
```php
if ($browser->wasFileUploaded('file')) {
    // File uploaded successfully
    processFile();
}
```

**After (alpha8+):**
```php
try {
    $browser->wasFileUploaded('file');
    // File uploaded successfully - no exception means success
    processFile();
} catch (Horde_Browser_Exception $e) {
    // Handle upload error
    showError($e->getMessage());
}
```

**Lost Feature:** Nested array field names like `files[0]` no longer supported. Use simple field names only.

---

### 2. downloadHeaders() Parameter Requirement

**Change:** `$filename` parameter now **required** (had default `'unknown'` in alpha4).

**Before (alpha4):**
```php
$browser->downloadHeaders();  // Used 'unknown' as filename
```

**After (alpha8+):**
```php
$browser->downloadHeaders('document.pdf');  // Filename required
```

**Impact:** Low - analysis shows all Horde applications already pass filename parameter.

---

## Class Name Changes (PSR-4 Migration)

### Legacy Classes (lib/) - Still Available

These classes remain in `lib/` for backward compatibility:

| Legacy Class | Location | Status |
|--------------|----------|--------|
| `Horde_Browser` | `lib/Horde/Browser.php` | ✅ Wrapper (delegates to modern) |
| `Horde_Browser_Exception` | `lib/Horde/Browser/Exception.php` | ✅ Available |
| `Horde_Browser_Translation` | `lib/Horde/Browser/Translation.php` | ✅ Available |

**Usage:** The lib/ directory is deprecated but still available as a migration aid. Integrators are expect to upgrade their code to the PSR-4 equivalents.

---

### Modern Classes (src/) - New PSR-4

New modern implementation in `src/` for new code:

| Modern Class | Namespace | Replaces |
|--------------|-----------|----------|
| `Horde\Browser\Browser` | `Horde\Browser` | `Horde_Browser` |
| `Horde\Browser\BrowserFamily` | `Horde\Browser` | String values ('webkit', 'mozilla') |
| `Horde\Browser\Platform` | `Horde\Browser` | String values ('win', 'mac', 'unix') |
| `Horde\Browser\BrowserException` | `Horde\Browser` | `Horde_Browser_Exception` |

**Usage migration to modern code:**
```php
use Horde\Browser\Browser;
use Horde\Browser\BrowserFamily;
use Horde\Browser\Platform;

$browser = new Browser();

// Modern enum-based detection
if ($browser->getBrowser() === BrowserFamily::Chrome) {
    // Chrome-specific code
}

if ($browser->getPlatform() === Platform::IPhone) {
    // iPhone-specific code
}

// Integer version numbers
$major = $browser->getMajorVersion();  // Returns: int 121
$minor = $browser->getMinorVersion();  // Returns: int 5
```

---

## Improvements (Non-Breaking)

### Fixed: Tablet Detection

**Before (alpha4):**
```php
$browser->isTablet()  // BROKEN - always returned false for iPad/Android tablets
```

**After (alpha8+):**
```php
$browser->isTablet()  // WORKS - correctly detects iPad and Android tablets
```

---

### Fixed: Firefox Version Detection

**Before (alpha4):**
```php
$browser->getMajor()  // Firefox 121 → returned "5" (Mozilla version!)
```

**After (alpha8+):**
```php
$browser->getMajor()  // Firefox 121 → returns "121" (correct!)
```

---

### Improved: Version Formatting

- `getMajor()` returns clean string: `"121"`
- `getMinor()` returns clean integer: `5` (not `"5.6543"`)
- `getVersion()` omits zero minor: `"121"` instead of `"121.0"`

---

## Required Actions for Integrators

### 1. Update File Upload Code

**Find and fix:**
```bash
grep -r "wasFileUploaded" --include="*.php" your-app/
```

**Change all instances from:**
```php
if ($browser->wasFileUploaded('field')) { ... }
```

**To:**
```php
try {
    $browser->wasFileUploaded('field');
    // ... success code
} catch (Horde_Browser_Exception $e) {
    // ... error handling
}
```

---

### 2. Verify downloadHeaders() Calls

**Check (but likely already correct):**
```bash
grep -r "downloadHeaders" --include="*.php" your-app/
```

Ensure all calls provide filename as first parameter:
```php
// ✅ Correct
$browser->downloadHeaders('file.pdf', 'application/pdf');

// ❌ Will cause TypeError
$browser->downloadHeaders();  // Missing filename
```

---

### 3. Test Tablet Detection

If your app has tablet-specific UI, **test on iPad and Android tablets**:
```php
if ($browser->isTablet()) {
    // This NOW WORKS for iPad/Android tablets
    serveTabletUI();
}
```

---

### 4. Update Firefox Version Checks

If you have Firefox version checks, they now work correctly:
```php
if ($browser->getBrowser() === 'mozilla' && (int)$browser->getMajor() >= 120) {
    // This now uses actual Firefox version (not Mozilla version)
    useFirefox120Features();
}
```

---

## Optional: Migrate to Modern API

For new code, consider using modern PSR-4 classes:

```php
// Old way (still works)
$browser = new Horde_Browser();
if ($browser->getBrowser() === 'webkit') { ... }

// New way (cleaner)
use Horde\Browser\Browser;
use Horde\Browser\BrowserFamily;

$browser = new Browser();
if ($browser->getBrowser() === BrowserFamily::Chrome) { ... }
```

**Benefits:**
- Type-safe enums
- Integer version numbers
- Cleaner API
- Better IDE autocomplete

---

## Testing Checklist

- [ ] File upload forms work (especially with error conditions)
- [ ] File downloads have correct filenames
- [ ] Tablet UI displays correctly on iPad/Android tablets
- [ ] Firefox version detection works
- [ ] All browser-specific quirks still work

---

## Summary

| Change | Type | Action Required |
|--------|------|-----------------|
| `wasFileUploaded()` | Breaking | ✅ Update to try-catch |
| `downloadHeaders()` | Breaking | ⚠️ Check (likely already correct) |
| Tablet detection | Fixed | ✅ Test tablet UI |
| Firefox version | Fixed | ✅ Update version checks |
| PSR-4 classes | New | ⏸️ Optional migration |

---

## Known Packages Using horde/browser

**Note:** This is a non-exhaustive list of Horde packages using horde/browser. Your application may use horde/browser even if not listed here.

The following Horde applications are known to use `Horde_Browser` (primarily via `downloadHeaders()`, but may use other methods):

### Core Libraries
- **horde/data** - Data import/export (CSV, TSV, ICalendar exports)

### Applications
- **ansel** - Photo gallery (downloads, zip exports, RSS feeds)
- **imp** - Webmail (email attachments, message downloads)
- **kronolith** - Calendar (exports, free/busy, viewer)
- **whups** - Tickets (RSS feeds, file downloads)
- **mnemo** - Notes (PDF exports)
- **jonah** - News aggregator (RSS delivery)
- **wicked** - Wiki (page downloads)
- **trean** - Bookmarks (favicon downloads)
- **hylax** - Fax (viewing)
- **hermes** - Time tracking (IIF exports)
- **agora** - Forums (attachments)

### Checking Your Application

Search for browser usage in your codebase:

```bash
# Check for Horde_Browser usage
grep -r "Horde_Browser" --include="*.php" your-app/
grep -r "new.*Browser()" --include="*.php" your-app/

# Check for specific methods
grep -r "wasFileUploaded\|downloadHeaders\|isViewable\|isMobile" --include="*.php" your-app/
```

---

## Support

- **Issues:** https://github.com/horde/Browser/issues
- **Docs:** https://www.horde.org/libraries/Horde_Browser
- **Analysis:** See `horde-development/browser-3.0.0alpha8-behavioral-changes.md`
