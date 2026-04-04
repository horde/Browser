# Horde Browser 3.0.0 Migration Guide

**Version:** 3.0.0alpha4 → 3.0.0beta2+
**Date:** 2026-04-04

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

**Note:** Both legacy `Horde_Browser` (lib/) and modern `Horde\Browser\HttpUtils` support nested array notation like `object[photo][new]`. For new PSR-7 based code, prefer `HttpUtils` (see HTTP Utilities Migration section).

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

---

## Class Name Changes (PSR-4 Migration)

### Legacy Classes (lib/) - Still Available

These classes remain in `lib/` for backward compatibility:

| Legacy Class | Location | Status |
|--------------|----------|--------|
| `Horde_Browser` | `lib/Horde/Browser.php` | Wrapper (delegates to modern) |
| `Horde_Browser_Exception` | `lib/Horde/Browser/Exception.php` | Available |
| `Horde_Browser_Translation` | `lib/Horde/Browser/Translation.php` | Available |

**Usage:** The lib/ directory is deprecated but still available as a migration aid. Integrators are expect to upgrade their code to the PSR-4 equivalents.

---

### Modern Classes (src/) - New PSR-4

New modern implementation in `src/` for new code:

| Modern Class | Namespace | Replaces |
|--------------|-----------|----------|
| `Horde\Browser\Browser` | `Horde\Browser` | `Horde_Browser` |
| `Horde\Browser\BrowserFamily` | `Horde\Browser` | String values ('webkit', 'mozilla') |
| `Horde\Browser\Platform` | `Horde\Browser` | String values ('win', 'mac', 'unix') |
| `Horde\Browser\Exception` | `Horde\Browser` | `Horde_Browser_Exception` |

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

**Before:**
```php
$browser->getMajor()  // Firefox 121 → returned "5" (Mozilla version!)
```

**After:**
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

### 1. Update Exception Handling

**Find and update:**
```bash
grep -r "wasFileUploaded\|downloadHeaders" --include="*.php" your-app/
```

**Wrap in try-catch:**
```php
try {
    $browser->wasFileUploaded('field');
    $browser->downloadHeaders('file.pdf', 'application/pdf');
    // ... success code
} catch (Horde_Browser_Exception $e) {
    // ... error handling
}
```

**Ensure downloadHeaders() has filename:**
```php
// OK
$browser->downloadHeaders('file.pdf', 'application/pdf');

// Will cause TypeError
$browser->downloadHeaders();  // Missing filename
```

---

### 2. Test Tablet Detection

If your app has tablet-specific UI, **test on iPad and Android tablets**:
```php
if ($browser->isTablet()) {
    // This NOW WORKS for iPad/Android tablets
    serveTabletUI();
}
```

---

### 3. Update Firefox Version Checks

If you have Firefox version checks, they now work correctly:
```php
if ($browser->getBrowser() === 'mozilla' && (int)$browser->getMajor() >= 120) {
    // This now uses actual Firefox version (not Mozilla version)
    useFirefox120Features();
}
```

---

## Modern PSR-4 Migration

### Browser Class Migration

For new code, use modern PSR-4 classes with type-safe enums:

```php
// Legacy (still works)
$browser = new Horde_Browser();
if ($browser->getBrowser() === 'webkit') { ... }

// Modern
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

### HTTP Utilities Migration

Browser 3.0 introduces `Horde\Browser\HttpUtils`, a PSR-7 based replacement for HTTP utility methods.

**Basic usage:**
```php
use Horde\Browser\HttpUtils;

// From superglobals
$utils = HttpUtils::fromGlobals();

// Or inject PSR-7 request
$utils = new HttpUtils($serverRequest);
```

**Key improvements:**
- PSR-7 ServerRequestInterface support
- Dependency injection friendly
- Nested array notation support in `wasFileUploaded()`
- Fully unit tested

---

#### Method Reference

**allowFileUploads()** - Check upload limits

**Legacy (lib/):**
```php
$maxSize = Horde_Browser::allowFileUploads();
```

**Modern (PSR-7):**
```php
use Horde\Browser\HttpUtils;

$maxSize = HttpUtils::allowFileUploads();  // Static method
```

**Native horde/http:** No direct equivalent. Check `ini_get('file_uploads')`, `ini_get('upload_max_filesize')`, and `ini_get('post_max_size')` manually.

---

**wasFileUploaded()** - Validate file uploads

**Legacy (lib/):**
```php
$browser = new Horde_Browser();
try {
    $browser->wasFileUploaded('photo', 'user photo');
} catch (Horde_Browser_Exception $e) {
    // Handle error
}
```

**Modern (PSR-7):**
```php
use Horde\Browser\HttpUtils;

$utils = HttpUtils::fromGlobals();
try {
    $utils->wasFileUploaded('photo', 'user photo');
} catch (Horde\Browser\Exception $e) {
    // Handle error
}
```

**Supports nested array notation:**
```php
// Both simple and nested field names work
$utils->wasFileUploaded('photo');                  // Simple
$utils->wasFileUploaded('object[photo][new]');     // Nested
```

**Native horde/http:**
```php
use Horde\Http\ServerRequest;

$request = ServerRequest::fromGlobals();
$uploadedFiles = $request->getUploadedFiles();

if (isset($uploadedFiles['photo'])) {
    $file = $uploadedFiles['photo'];
    if ($file->getError() === UPLOAD_ERR_OK && $file->getSize() > 0) {
        // File uploaded successfully
    }
}
```

---

**downloadHeaders()** - Send file download headers

**Legacy (lib/):**
```php
$browser = new Horde_Browser();
$browser->downloadHeaders('report.pdf', 'application/pdf', false, 12345);
```

**Modern (PSR-7):**
```php
use Horde\Browser\HttpUtils;

$utils = HttpUtils::fromGlobals();
$utils->downloadHeaders('report.pdf', 'application/pdf', false, 12345);
```

**Native horde/http:**
```php
use Horde\Http\Response;
use Horde\Http\Stream;

$stream = new Stream(fopen('report.pdf', 'r'));
$response = new Response(
    200,
    [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'attachment; filename*=UTF-8\'\'report.pdf',
        'Content-Length' => '12345',
        'Cache-Control' => 'no-cache, must-revalidate',
    ],
    $stream
);
```

---

**getHTTPProtocol()** - Get protocol version

**Legacy (lib/):**
```php
$browser = new Horde_Browser();
$protocol = $browser->getHTTPProtocol();  // "1.1"
```

**Modern (PSR-7):**
```php
use Horde\Browser\HttpUtils;

$utils = HttpUtils::fromGlobals();
$protocol = $utils->getHTTPProtocol();  // "1.1"
```

**Native horde/http:**
```php
use Horde\Http\ServerRequest;

$request = ServerRequest::fromGlobals();
$protocol = $request->getProtocolVersion();  // "1.1"
```

---

**getIPAddress()** - Get client IP address

**Legacy (lib/):**
```php
$browser = new Horde_Browser();
$ip = $browser->getIPAddress();  // "192.168.1.100"
```

**Modern (PSR-7):**
```php
use Horde\Browser\HttpUtils;

$utils = HttpUtils::fromGlobals();
$ip = $utils->getIPAddress();  // "192.168.1.100"
```

**Checks X-Forwarded-For header for proxied requests:**
```php
// Returns first IP from X-Forwarded-For if present
// Falls back to REMOTE_ADDR
```

**Native horde/http:**
```php
use Horde\Http\ServerRequest;

$request = ServerRequest::fromGlobals();
$forwardedFor = $request->getHeaderLine('X-Forwarded-For');
if ($forwardedFor !== '') {
    $ips = explode(',', $forwardedFor);
    $ip = trim($ips[0]);
} else {
    $serverParams = $request->getServerParams();
    $ip = $serverParams['REMOTE_ADDR'] ?? '';
}
```

---

**usingSSLConnection()** - Check for HTTPS

**Legacy (lib/):**
```php
$browser = new Horde_Browser();
$isSSL = $browser->usingSSLConnection();  // true/false
```

**Modern (PSR-7):**
```php
use Horde\Browser\HttpUtils;

$utils = HttpUtils::fromGlobals();
$isSSL = $utils->usingSSLConnection();  // true/false
```

**Native horde/http:**
```php
use Horde\Http\ServerRequest;

$request = ServerRequest::fromGlobals();
$uri = $request->getUri();
$isSSL = $uri->getScheme() === 'https';

// Or check server params
$serverParams = $request->getServerParams();
$isSSL = !empty($serverParams['SSL_PROTOCOL_VERSION']);
```

---

### Quick Migration Guide

**Step 1: Find usage**
```bash
grep -r "allowFileUploads\|wasFileUploaded\|downloadHeaders\|getHTTPProtocol\|getIPAddress\|usingSSLConnection" --include="*.php" your-app/
```

**Step 2: Update code**
```php
// Before
$browser = new Horde_Browser();
$browser->wasFileUploaded('file');

// After
use Horde\Browser\HttpUtils;

$utils = HttpUtils::fromGlobals();
$utils->wasFileUploaded('file');
```

**Step 3: Update exceptions**
```php
// Before
catch (Horde_Browser_Exception $e) { ... }

// After
catch (Horde\Browser\Exception $e) { ... }
```

**Step 4: Enable nested fields (optional)**
```php
// Now supported in HttpUtils (not in legacy lib/)
$utils->wasFileUploaded('object[photo][new]');
```

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
| `wasFileUploaded()` | Breaking | Update to try-catch |
| `downloadHeaders()` | Breaking | Check (likely already correct) |
| Tablet detection | Fixed | Test tablet UI |
| Firefox version | Fixed | Update version checks |
| PSR-4 classes | New | Optional migration |

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
