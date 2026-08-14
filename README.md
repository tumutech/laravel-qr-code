# Laravel QR Code

Branded, scannable QR codes inside Laravel — center logos, style templates, scan-safe checks, HTTP responses, Blade, storage, and data URIs for Inertia.

```bash
composer require tumutech/laravel-qr-code
```

Laravel auto-discovers the package. No extra setup.

Optional defaults:

```bash
php artisan vendor:publish --tag=qr-code-config
```

## What it offers

- **Center brand logo** with a padded punchout so modules do not show through
- **`scanSafe()`** — contrast, quiet zone, and logo-size checks before you ship
- Named **templates** and frames for consistent product branding
- HTTP responses, Blade embeds, disk storage, and data URIs for Inertia/React
- PNG, SVG, WebP, EPS, PDF, and raw binary
- Wi-Fi, email, SMS, and vCard payloads

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12
- GD extension for PNG / WebP

## Usage

```php
use Tumutech\QrCode\Facades\QrCode;

return QrCode::size(300)->png('https://example.com');
```

### Center logo

Put your mark in the middle. Error correction is raised to `high` automatically. By default the logo is ~22% of the QR size, with a rounded background punchout behind it.

```php
return QrCode::size(400)
    ->logo(public_path('images/brand.png'))
    ->scanSafe()
    ->png('https://example.com/invite');
```

Optional pixel width, or disable the punchout pad:

```php
QrCode::logo(public_path('images/brand.png'), 88);          // custom width
QrCode::logo(public_path('images/brand.png'), null, false); // no punchout
```

Keep the logo small enough to scan. `scanSafe()` rejects logos that cover too much of the code.

### Style + brand in one call

```php
QrCode::template('gradient-ocean')
    ->logo(public_path('images/brand.png'))
    ->scanSafe()
    ->png('https://example.com');

QrCode::module('dots', 0.8)
    ->eye('circle')
    ->color('#101824')
    ->backgroundColor('#ffffff')
    ->logo(public_path('images/brand.png'))
    ->scanSafe()
    ->png('https://example.com');
```

Styled codes support **PNG** and **SVG**.

| Option | Values |
| --- | --- |
| `module()` | `square`, `dots`, `rounded` |
| `eye()` | `square`, `circle`, `pointy` |
| `frame()` | `none`, `badge`, `card` |
| `gradient()` | start, end, direction: `vertical`, `horizontal`, `diagonal`, `inverse_diagonal`, `radial` |

Templates: `classic`, `classic-inverted`, `dots-circle`, `dots-square-eyes`, `dots-pointy`, `dots-small`, `dots-large`, `rounded-soft`, `rounded-medium`, `rounded-strong`, `gradient-ocean`, `gradient-sunset`, `gradient-aurora`, `gradient-forest`, `gradient-inverse`, `pointy-brand`, `circle-eyes-square`, `badge-brand`, `card-dots`.

### Embed in Blade

```blade
<x-qr-code data="https://example.com" :size="200" />

<x-qr-code
    data="https://example.com/invite"
    :size="240"
    :logo="public_path('images/brand.png')"
    template="dots-circle"
/>

<img src="@qrcode('https://example.com')" alt="QR">
```

When `logo` is set, the Blade component turns on `scanSafe()` for you.

### Inertia / React

Generate on Laravel and pass a data URI:

```php
return Inertia::render('Ticket', [
    'qr' => QrCode::size(240)
        ->logo(public_path('images/brand.png'))
        ->scanSafe()
        ->dataUri($ticketUrl),
]);
```

```jsx
<img src={qr} width={240} height={240} alt="Ticket QR" />
```

### Save to storage

```php
QrCode::logo(public_path('images/brand.png'))
    ->scanSafe()
    ->store('https://example.com', 'qrs/ticket.png');

QrCode::disk('s3')->store('https://example.com', 'qrs/ticket.png');
```

### Wi-Fi, email, SMS, vCard

```php
$qr = QrCode::wifi([
    'ssid' => 'Office',
    'password' => 'secret',
]);

echo $qr->getDataUri();

QrCode::email('hello@example.com', 'Subject', 'Body');
QrCode::sms('+15551234567', 'Hello');
QrCode::vcard([
    'firstName' => 'Ada',
    'lastName' => 'Lovelace',
    'organization' => 'Example',
    'phones' => ['+15551234567'],
    'emails' => ['ada@example.com'],
]);
```

## Everyday methods

| Method | What it does |
| --- | --- |
| `logo()` | Center brand mark with optional punchout |
| `scanSafe()` | Contrast, quiet zone, logo coverage checks |
| `template()` / `frame()` | Brand presets and print frames |
| `module()` / `eye()` / `gradient()` | Module shape, finder eyes, fill |
| `size()` / `margin()` / `format()` | Canvas size and output type |
| `color()` / `backgroundColor()` | RGB or `#hex` |
| `png()` / `svg()` / `response()` | HTTP image |
| `dataUri()` / `generate()` | Embed or get raw bytes |
| `store()` / `disk()` | Save to Laravel storage |
| `wifi()` / `email()` / `sms()` / `vcard()` | Structured payloads |

## Config

Published file: `config/qr-code.php`

Defaults you can set: format, size, margin, error correction, colors, and storage disk.

## Help: enable PHP GD

PNG and WebP need the **GD** PHP extension. Composer cannot install it for you.

Check:

```bash
php -m | grep -i gd
```

If nothing shows, enable it:

**Ubuntu / Debian**

```bash
sudo apt install php-gd
sudo systemctl restart php8.2-fpm   # or your PHP-FPM / Apache service
```

**Windows**

In `php.ini`, uncomment:

```ini
extension=gd
```

Restart Apache, Nginx+PHP-FPM, or `php artisan serve`.

**Laravel Sail / Docker**

Install `gd` in the image (Sail usually includes it). Rebuild if needed:

```bash
sail build --no-cache
```

Then confirm again with `php -m | grep -i gd`.

SVG output can still work without GD. Styled PNG uses GD when Imagick is not installed.

## Credits

This package uses:

- [bacon/bacon-qr-code](https://github.com/Bacon/BaconQrCode) (BSD-2-Clause)
- [endroid/qr-code](https://github.com/endroid/qr-code) (MIT)

Their license notices ship with Composer under `vendor/`. Full texts: [Bacon LICENSE](https://github.com/Bacon/BaconQrCode/blob/master/LICENSE), [Endroid LICENSE](https://github.com/endroid/qr-code/blob/main/LICENSE).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

MIT
