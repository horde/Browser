# Horde Browser

Browser detection library with HTTP utilities.

## Installation

```bash
composer require horde/browser
```

## Usage

### Browser Detection

```php
use Horde\Browser\Browser;

$browser = new Browser($_SERVER['HTTP_USER_AGENT']);
echo $browser->getBrowserName();  // "chrome"
echo $browser->getMajorVersion(); // 120
echo $browser->getPlatformName(); // "windows"
$browser->mobile();                // false
```

### HTTP Utilities (PSR-7)

```php
use Horde\Browser\HttpUtils;

// From PSR-7 request
$utils = new HttpUtils($serverRequest);

// From superglobals
$utils = HttpUtils::fromGlobals();

// Validate file upload (supports nested arrays)
$utils->wasFileUploaded('photo');
$utils->wasFileUploaded('object[photo][new]');

// Get request info
$utils->getIPAddress();
$utils->usingSSLConnection();
$utils->getHTTPProtocol();
$utils->downloadHeaders('file.pdf', 'application/pdf');
```

## Links

- [Migration Guide](doc/MIGRATION.md)
- [Changelog](doc/changelog.yml)
- [GitHub](https://github.com/horde/Browser)
- [Documentation](https://www.horde.org/libraries/Horde_Browser)

## License

LGPL-2.1-only
