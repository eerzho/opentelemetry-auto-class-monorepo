# opentelemetry-auto-class-symfony

[![Version](https://img.shields.io/packagist/v/eerzho/opentelemetry-auto-class-symfony)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-symfony)
[![Downloads](https://img.shields.io/packagist/dt/eerzho/opentelemetry-auto-class-symfony)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-symfony)
[![PHP](https://img.shields.io/packagist/dependency-v/eerzho/opentelemetry-auto-class-symfony/php)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-symfony)
[![License](https://img.shields.io/packagist/l/eerzho/opentelemetry-auto-class-symfony)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-symfony)

Trace what your Symfony methods received, returned, and threw — without writing a single span.

The Symfony integration for [opentelemetry-auto-class](https://github.com/eerzho/opentelemetry-auto-class) — your classes are discovered and registered automatically.

This is a read-only sub-split. Please open issues and pull requests in the [monorepo](https://github.com/eerzho/opentelemetry-auto-class-monorepo).

## Installation

```bash
composer require eerzho/opentelemetry-auto-class-symfony
```

Register the bundle:

```php
// config/bundles.php
return [
    // ...
    Eerzho\Instrumentation\Class\Symfony\AutoClassBundle::class => ['all' => true],
];
```

Requirements:
- [ext-opentelemetry](https://opentelemetry.io/docs/zero-code/php/)
- PHP 8.2+
- Symfony 6+

## Usage

Add `#[Trace]` to any class registered as a service:

```php
namespace App\Service;

use Eerzho\Instrumentation\Class\Attribute\Trace;
use Eerzho\Instrumentation\Class\Attribute\TraceMethod;
use Eerzho\Instrumentation\Class\Attribute\TraceProperties;

#[Trace]                                   // mark the class for tracing
class OrderService
{
    // span "App\Service\OrderService::pay"
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

1. During container compilation, scans all service definitions for the `#[Trace]` attribute
2. Builds a method map and stores it as a container parameter
3. On kernel boot, registers `ext-opentelemetry` hooks for matched methods

> Only classes registered as services are discovered — a `#[Trace]` class that is never wired into the container is not instrumented.

## Disabling instrumentation

```bash
OTEL_PHP_DISABLED_INSTRUMENTATIONS=class
```
