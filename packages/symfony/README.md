# opentelemetry-auto-class-symfony

[![Version](https://img.shields.io/packagist/v/eerzho/opentelemetry-auto-class-symfony)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-symfony)
[![Downloads](https://img.shields.io/packagist/dt/eerzho/opentelemetry-auto-class-symfony)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-symfony)
[![PHP](https://img.shields.io/packagist/dependency-v/eerzho/opentelemetry-auto-class-symfony/php)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-symfony)
[![License](https://img.shields.io/packagist/l/eerzho/opentelemetry-auto-class-symfony)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-symfony)

Symfony integration for [opentelemetry-auto-class](https://github.com/eerzho/opentelemetry-auto-class). Scans your service container for `#[Trace]` classes at compile time and instruments them — no manual registration.

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

## Configuration

None. The bundle instruments any service in the container carrying `#[Trace]` — there is nothing to configure.

## Usage

Add `#[Trace]` to any class registered as a service:

```php
namespace App\Service;

use Eerzho\Instrumentation\Class\Attribute\Trace;

#[Trace]
class OrderService
{
    public function create(array $items): void
    {
        // span "App\Service\OrderService::create" is created automatically
    }
}
```

Attribute options (`include`/`exclude`, argument capture, serialization, exception handling) are documented in the [core README](https://github.com/eerzho/opentelemetry-auto-class).

## How it works

1. During container compilation, the bundle scans all service definitions for the `#[Trace]` attribute
2. Builds a method map and stores it as a container parameter
3. On kernel boot, registers `ext-opentelemetry` hooks for matched methods

> Only classes registered as services are discovered — a `#[Trace]` class that is never wired into the container is not instrumented.

## Disabling instrumentation

```bash
OTEL_PHP_DISABLED_INSTRUMENTATIONS=class
```

## License

[MIT](LICENSE)
