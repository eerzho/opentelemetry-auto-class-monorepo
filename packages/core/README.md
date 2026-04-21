# opentelemetry-auto-class

[![Version](https://img.shields.io/packagist/v/eerzho/opentelemetry-auto-class)](https://packagist.org/packages/eerzho/opentelemetry-auto-class)
[![Downloads](https://img.shields.io/packagist/dt/eerzho/opentelemetry-auto-class)](https://packagist.org/packages/eerzho/opentelemetry-auto-class)
[![PHP](https://img.shields.io/packagist/dependency-v/eerzho/opentelemetry-auto-class/php)](https://packagist.org/packages/eerzho/opentelemetry-auto-class)
[![License](https://img.shields.io/packagist/l/eerzho/opentelemetry-auto-class)](https://packagist.org/packages/eerzho/opentelemetry-auto-class)

Automatic OpenTelemetry tracing for PHP methods via the `#[Traceable]` attribute. Framework-agnostic core — mark any class with the attribute, and spans are created automatically using the `ext-opentelemetry` hook API.

This is a read-only sub-split. Please open issues and pull requests in the [monorepo](https://github.com/eerzho/opentelemetry-auto-class-monorepo).

## Installation

```bash
composer require eerzho/opentelemetry-auto-class
```

Requirements:
- [ext-opentelemetry](https://opentelemetry.io/docs/zero-code/php/)
- PHP 8.2+

## Usage

### Basic

Add `#[Traceable]` to a class — all public methods will be traced automatically:

```php
use OpenTelemetry\Contrib\Instrumentation\Class\Attribute\Traceable;
use OpenTelemetry\Contrib\Instrumentation\Class\AttributeScanner;
use OpenTelemetry\Contrib\Instrumentation\Class\ClassInstrumentation;

#[Traceable]
class OrderService
{
    public function create(array $items): void
    {
        // span "OrderService::create" is created automatically
    }

    public function cancel(int $orderId): void
    {
        // span "OrderService::cancel" is created automatically
    }
}

// Register instrumentation
$map = AttributeScanner::scan([OrderService::class]);
ClassInstrumentation::register($map);
```

> For Laravel and Symfony, use the framework integrations that handle class discovery automatically:
> [opentelemetry-auto-class-laravel](https://github.com/eerzho/opentelemetry-auto-class-laravel) / [opentelemetry-auto-class-symfony](https://github.com/eerzho/opentelemetry-auto-class-symfony)

### Exclude methods

Use the `exclude` parameter to skip specific methods from tracing:

```php
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

### Without attributes

You can register classes for tracing without using `#[Traceable]` — build the method map manually and pass it to `ClassInstrumentation::register()`:

```php
class OrderService
{
    public function create(array $items, int $priority, string $note): void {}

    public function cancel(int $orderId): void {}

    public function archive(int $orderId): void {}
}
```

```php
use OpenTelemetry\Contrib\Instrumentation\Class\ClassInstrumentation;

ClassInstrumentation::register([
    OrderService::class => [
        // trace with arguments, skip 'priority' (position 1)
        'create' => ['items' => 0, 'note' => 2],
        // trace without capturing arguments
        'cancel' => [],
        // 'archive' is not listed — it will NOT be traced
    ],
]);
```

Each entry maps a class to its methods, and each method to its arguments (`parameter name => position`). Methods not listed are not traced. Arguments not listed are not captured.

## How it works

Each traced method call produces a span:

- Name: `ClassName::methodName`
- Kind: `INTERNAL`

With attributes:

| Attribute            | Value                             |
|----------------------|-----------------------------------|
| `code.function.name` | `ClassName::methodName`           |
| `code.file.path`     | File where the method is defined  |
| `code.line.number`   | Line number of the method         |
| Method arguments     | Parameter name → serialized value |

If a method throws an exception, the span records an `exception` event and sets status to `ERROR`:

| Event attribute        | Value                |
|------------------------|----------------------|
| `exception.type`       | Exception class name |
| `exception.message`    | Exception message    |
| `exception.stacktrace` | Full stack trace     |

## Argument serialization

Arguments are serialized to span-compatible types:

| Type                             | Result             | Example                         |
|----------------------------------|--------------------|---------------------------------|
| `string`, `int`, `float`, `bool` | As-is              | `"hello"`, `42`, `3.14`, `true` |
| `null`                           | `"null"`           | `null` → `"null"`               |
| `BackedEnum`                     | Backing value      | `Status::Active` → `"active"`   |
| Object with `__toString()`       | String cast        | `$money` → `"100 USD"`          |
| Object without `__toString()`    | Class name (FQCN)  | `$dto` → `"App\DTO\Order"`      |
| `array`                          | JSON string        | `[1, 2]` → `"[1,2]"`            |
| Other (`resource`, ...)          | `gettype()` result | `"resource"`                    |

If JSON encoding of an array fails, the value is serialized as `"array"`.

## Disabling instrumentation

To disable tracing at runtime, use the standard OpenTelemetry environment variable:

```bash
OTEL_PHP_DISABLED_INSTRUMENTATIONS=class
```

## Limitations

- Only **public** methods are traced (protected and private are ignored)
- Abstract classes, interfaces, traits, and enums are skipped
- Requires `ext-opentelemetry` installed and loaded

## License

[MIT](LICENSE)
