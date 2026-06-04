# opentelemetry-auto-class-symfony

[![Version](https://img.shields.io/packagist/v/eerzho/opentelemetry-auto-class-symfony)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-symfony)
[![Downloads](https://img.shields.io/packagist/dt/eerzho/opentelemetry-auto-class-symfony)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-symfony)
[![PHP](https://img.shields.io/packagist/dependency-v/eerzho/opentelemetry-auto-class-symfony/php)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-symfony)
[![License](https://img.shields.io/packagist/l/eerzho/opentelemetry-auto-class-symfony)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-symfony)

Symfony integration for automatic OpenTelemetry tracing of PHP methods via the `#[Traceable]` attribute. All services with the attribute in the container are instrumented automatically using the `ext-opentelemetry` hook API.

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
    Eerzho\Instrumentation\Class\Symfony\TraceableBundle::class => ['all' => true],
];
```

Requirements:
- [ext-opentelemetry](https://opentelemetry.io/docs/zero-code/php/)
- PHP 8.2+
- Symfony 6+

## Usage

### Basic

Add `#[Traceable]` to a class registered in the service container — all public methods will be traced automatically:

```php
namespace App\Service;

use Eerzho\Instrumentation\Class\Attribute\Traceable;

#[Traceable]
class OrderService
{
    public function create(array $items): void
    {
        // span "App\Service\OrderService::create" is created automatically
    }

    public function cancel(int $orderId): void
    {
        // span "App\Service\OrderService::cancel" is created automatically
    }
}
```

> For full details on how spans are created, argument serialization, and limitations, see [opentelemetry-auto-class](https://github.com/eerzho/opentelemetry-auto-class).

### Exclude methods

Use the `exclude` parameter to skip specific methods from tracing:

```php
namespace App\Service;

use Eerzho\Instrumentation\Class\Attribute\Traceable;

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
namespace App\Service;

use Eerzho\Instrumentation\Class\Attribute\Arguments;
use Eerzho\Instrumentation\Class\Attribute\Traceable;

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

1. During container compilation, the bundle scans all service definitions for `#[Traceable]` attribute
2. Builds a method map and stores it as a container parameter
3. On kernel boot, registers `ext-opentelemetry` hooks for matched methods

## Disabling instrumentation

To disable tracing at runtime, use the standard OpenTelemetry environment variable:

```bash
OTEL_PHP_DISABLED_INSTRUMENTATIONS=class
```


## License

[MIT](LICENSE)
