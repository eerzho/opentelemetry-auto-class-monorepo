# opentelemetry-auto-class-laravel

[![Version](https://img.shields.io/packagist/v/eerzho/opentelemetry-auto-class-laravel)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-laravel)
[![Downloads](https://img.shields.io/packagist/dt/eerzho/opentelemetry-auto-class-laravel)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-laravel)
[![PHP](https://img.shields.io/packagist/dependency-v/eerzho/opentelemetry-auto-class-laravel/php)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-laravel)
[![License](https://img.shields.io/packagist/l/eerzho/opentelemetry-auto-class-laravel)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-laravel)

Laravel integration for [opentelemetry-auto-class](https://github.com/eerzho/opentelemetry-auto-class). Discovers `#[Trace]` classes in your configured namespaces and instruments them on boot — no manual registration.

This is a read-only sub-split. Please open issues and pull requests in the [monorepo](https://github.com/eerzho/opentelemetry-auto-class-monorepo).

## Installation

```bash
composer require eerzho/opentelemetry-auto-class-laravel
```

Requirements:
- [ext-opentelemetry](https://opentelemetry.io/docs/zero-code/php/)
- PHP 8.2+
- Laravel 10+

The service provider is auto-discovered — no manual registration needed.

## Configuration

Scanned namespaces default to `App\`. To customize, publish the config:

```bash
php artisan vendor:publish --tag=trace-config
```

```php
// config/trace.php
return [
    'namespaces' => [
        'App\\Services\\',
        'App\\Jobs\\',
        'Domain\\',
    ],
];
```

## Usage

Add `#[Trace]` to any class in a scanned namespace:

```php
namespace App\Services;

use Eerzho\Instrumentation\Class\Attribute\Trace;

#[Trace]
class OrderService
{
    public function create(array $items): void
    {
        // span "App\Services\OrderService::create" is created automatically
    }
}
```

Attribute options (`include`/`exclude`, argument capture, serialization, exception handling) are documented in the [core README](https://github.com/eerzho/opentelemetry-auto-class).

## How it works

On boot the service provider:

1. Reads namespaces from `config/trace.php`
2. Discovers classes in those namespaces via Composer's `ClassLoader::getClassMap()`
3. Scans them for the `#[Trace]` attribute
4. Registers `ext-opentelemetry` hooks for matched methods

> Only classes present in the Composer class map are discovered — run `composer dump-autoload -o` in production so nothing is missed.

## Disabling instrumentation

```bash
OTEL_PHP_DISABLED_INSTRUMENTATIONS=class
```

## License

[MIT](LICENSE)
