# opentelemetry-auto-class-laravel

[![Version](https://img.shields.io/packagist/v/eerzho/opentelemetry-auto-class-laravel)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-laravel)
[![Downloads](https://img.shields.io/packagist/dt/eerzho/opentelemetry-auto-class-laravel)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-laravel)
[![PHP](https://img.shields.io/packagist/dependency-v/eerzho/opentelemetry-auto-class-laravel/php)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-laravel)
[![License](https://img.shields.io/packagist/l/eerzho/opentelemetry-auto-class-laravel)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-laravel)

Trace what your Laravel methods received, returned, and threw — without writing a single span.

The Laravel integration for [opentelemetry-auto-class](https://github.com/eerzho/opentelemetry-auto-class) — your classes are discovered and registered automatically.

This is a read-only sub-split. Please open issues and pull requests in the [monorepo](https://github.com/eerzho/opentelemetry-auto-class-monorepo).

## Installation

```bash
composer require eerzho/opentelemetry-auto-class-laravel
```

Requirements:
- [ext-opentelemetry](https://opentelemetry.io/docs/zero-code/php/)
- PHP 8.2+
- Laravel 10+

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
use Eerzho\Instrumentation\Class\Attribute\TraceMethod;
use Eerzho\Instrumentation\Class\Attribute\TraceProperties;

#[Trace]                                   // mark the class for tracing
class OrderService
{
    // span "App\Services\OrderService::pay"
    #[TraceMethod(exclude: ['card'])]   // hide "card" from the span
    public function pay(int $orderId, string $card, Address $address): void {}

    public function healthCheck(): bool {}   // no #[TraceMethod] -> not traced
}

#[TraceProperties(exclude: ['zip'])]       // expand every prop but zip
class Address
{
    public function __construct(public string $city, public string $zip) {}
}
```

All three attributes and their options are fully documented in the [core](https://github.com/eerzho/opentelemetry-auto-class).

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
