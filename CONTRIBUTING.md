# Contributing

Thanks for helping with `tumutech/laravel-qr-code`. Keep changes small and focused.

## Setup

```bash
composer install
```

PHP 8.2+ and the GD extension are required for tests that render PNG.

## Checks

Run these before you open a pull request:

```bash
composer format
composer analyse
composer test
```

- **Pint** — Laravel code style (`composer format`)
- **PHPStan** — level 6 on `src/` (`composer analyse`)
- **PHPUnit** — unit + feature tests via Orchestra Testbench (`composer test`)

CI runs the same suite on PHP 8.2–8.4 × Laravel 10–12.

## What to change

- Public API lives in `src/QrCodeBuilder.php` and `src/Facades/QrCode.php`. Keep the fluent builder immutable (`clone` then return).
- Center logos go through `src/Style/LogoComposer.php` (punchout pad + size defaults). Keep center-logo behavior scan-safe by default.
- New style options belong in `src/Enums/` and `src/Style/`. Add a template in `src/Style/StyleTemplates.php` only if it is a reusable preset, not a one-off demo.
- `scanSafe()` rules live in `src/Style/ScanSafeValidator.php`. If you loosen a check, explain why in the PR.
- Payload helpers (`wifi`, `email`, `sms`, `vcard`) live in `src/Data/`.
- Do not commit `vendor/`, `example/`, or `composer.lock`.

## Tests

Add or update a test next to the behavior you change:

- `tests/Unit/` — builder, payloads, styled output
- `tests/Feature/` — Laravel provider, Blade, HTTP, storage

Cover both the happy path and the failure you care about (invalid option, scan-unsafe style, missing logo file).

## Pull requests

1. One concern per PR.
2. Describe *why*, not only *what*.
3. Do not bump the package version in `composer.json` (releases are git tags).
4. Do not vendor Bacon, Endroid, or other dependencies.

## License

By contributing, you agree that your work is licensed under the MIT License in this repository.
