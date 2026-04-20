# opentelemetry-auto-class-laravel

[![Version](https://img.shields.io/packagist/v/eerzho/opentelemetry-auto-class-laravel)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-laravel)
[![Downloads](https://img.shields.io/packagist/dt/eerzho/opentelemetry-auto-class-laravel)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-laravel)
[![PHP](https://img.shields.io/packagist/dependency-v/eerzho/opentelemetry-auto-class-laravel/php)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-laravel)
[![License](https://img.shields.io/packagist/l/eerzho/opentelemetry-auto-class-laravel)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-laravel)

Laravel integration for automatic OpenTelemetry tracing of PHP methods via the `#[Traceable]` attribute. All classes with the attribute in configured namespaces are instrumented automatically using the `ext-opentelemetry` hook API.

This is a read-only sub-split. Please open issues and pull requests in the [monorepo](https://github.com/eerzho/opentelemetry-auto-class-monorepo).

## Installation

```bash
composer require eerzho/opentelemetry-auto-class-laravel
```

Optionally, publish the configuration to customize scanned namespaces (default is `App\`):

```bash
php artisan vendor:publish --tag=traceable-config
```

```php
// config/traceable.php
return [
    'namespaces' => [
        'App\\Services\\',
        'App\\Jobs\\',
        'Domain\\',
    ],
];
```

Requirements:
- [ext-opentelemetry](https://opentelemetry.io/docs/zero-code/php/)
- PHP 8.2+
- Laravel 10+

## Usage

### Basic

Add `#[Traceable]` to a class in the configured namespaces — all public methods will be traced automatically:

```php
namespace App\Services;

use OpenTelemetry\Contrib\Instrumentation\Class\Attribute\Traceable;

#[Traceable]
class OrderService
{
    public function create(array $items): void
    {
        // span "App\Services\OrderService::create" is created automatically
    }

    public function cancel(int $orderId): void
    {
        // span "App\Services\OrderService::cancel" is created automatically
    }
}
```

> Make sure to run `composer dump-autoload -o` so that all classes appear in the class map.

> For full details on how spans are created, argument serialization, and limitations, see [opentelemetry-auto-class](https://github.com/eerzho/opentelemetry-auto-class).

### Exclude methods

Use the `exclude` parameter to skip specific methods from tracing:

```php
namespace App\Services;

use OpenTelemetry\Contrib\Instrumentation\Class\Attribute\Traceable;

#[Traceable(exclude: ['healthCheck', 'getVersion'])]
class PaymentService
{
    public function charge(int $amount, string $currency): void
    {
        // traced
    }

    public function healthCheck(): bool
    {
        // NOT traced
        return true;
    }

    public function getVersion(): string
    {
        // NOT traced
        return '1.0.0';
    }
}
```

### Exclude arguments

By default, all method arguments are captured as span attributes. Use `#[Arguments(exclude: [...])]` on a method to hide sensitive parameters:

```php
namespace App\Services;

use OpenTelemetry\Contrib\Instrumentation\Class\Attribute\Arguments;
use OpenTelemetry\Contrib\Instrumentation\Class\Attribute\Traceable;

#[Traceable]
class AuthService
{
    #[Arguments(exclude: ['password', 'token'])]
    public function login(string $email, string $password, string $token): void
    {
        // span captures "email" attribute only
        // "password" and "token" are excluded
    }

    public function logout(int $userId): void
    {
        // span captures "userId" attribute (no exclusions)
    }
}
```

## How it works

1. On boot, the service provider reads namespaces from `config/traceable.php`
2. Discovers all classes in those namespaces via Composer's `ClassLoader::getClassMap()`
3. Scans discovered classes for `#[Traceable]` attribute
4. Registers `ext-opentelemetry` hooks for matched methods

## Disabling instrumentation

To disable tracing at runtime, use the standard OpenTelemetry environment variable:

```bash
OTEL_PHP_DISABLED_INSTRUMENTATIONS=class
```


## License

[MIT](LICENSE)
