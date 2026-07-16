# opentelemetry-auto-class

[![Version](https://img.shields.io/packagist/v/eerzho/opentelemetry-auto-class)](https://packagist.org/packages/eerzho/opentelemetry-auto-class)
[![Downloads](https://img.shields.io/packagist/dt/eerzho/opentelemetry-auto-class)](https://packagist.org/packages/eerzho/opentelemetry-auto-class)
[![PHP](https://img.shields.io/packagist/dependency-v/eerzho/opentelemetry-auto-class/php)](https://packagist.org/packages/eerzho/opentelemetry-auto-class)
[![License](https://img.shields.io/packagist/l/eerzho/opentelemetry-auto-class)](https://packagist.org/packages/eerzho/opentelemetry-auto-class)

Automatic OpenTelemetry tracing for PHP methods via the `#[Trace]` attribute. Framework-agnostic core — mark any class with the attribute, and spans are created automatically using the `ext-opentelemetry` hook API.

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

Add `#[Trace]` to a class — all public methods will be traced automatically:

```php
use Eerzho\Instrumentation\Class\Attribute\Trace;
use Eerzho\Instrumentation\Class\AttributeScanner;
use Eerzho\Instrumentation\Class\ClassInstrumentation;

#[Trace]
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

### Filtering methods (`include` / `exclude`)

`#[Trace]` traces all public methods by default. Narrow this down with `include` (allowlist) and/or `exclude` (denylist):

```php
use Eerzho\Instrumentation\Class\Attribute\Trace;

// Trace everything except these methods
#[Trace(exclude: ['healthCheck', 'getVersion'])]
class PaymentService { /* ... */ }

// Trace only these methods
#[Trace(include: ['charge', 'refund'])]
class BillingService { /* ... */ }
```

### Filtering arguments (`include` / `exclude`)

By default, all arguments of a traced method are captured as span attributes. Add `#[TraceArguments]` to a method to filter them — for example to hide sensitive parameters:

```php
use Eerzho\Instrumentation\Class\Attribute\TraceArguments;
use Eerzho\Instrumentation\Class\Attribute\Trace;

#[Trace]
class AuthService
{
    // Capture everything except "password" and "token"
    #[TraceArguments(exclude: ['password', 'token'])]
    public function login(string $email, string $password, string $token): void {}

    // Capture only "orderId"
    #[TraceArguments(include: ['orderId'])]
    public function pay(int $orderId, string $cardNumber): void {}

    public function logout(int $userId): void
    {
        // no #[TraceArguments] → "userId" is captured
    }
}
```

### How `include` and `exclude` combine

Both `#[Trace]` (methods) and `#[TraceArguments]` (arguments) follow the same rules:

| `include` | `exclude` | Result                                    |
|-----------|-----------|-------------------------------------------|
| `[]`      | `[]`      | Everything (default)                      |
| `[a, b]`  | `[]`      | Only `a` and `b`                          |
| `[]`      | `[a]`     | Everything except `a`                     |
| `[a, b]`  | `[b]`     | Only `a` — **`exclude` wins on conflict** |

An empty `include` means "no allowlist" (trace everything), **not** "trace nothing".

### Without attributes

You can register classes for tracing without using `#[Trace]` — build the method map manually and pass it to `ClassInstrumentation::register()`:

```php
class OrderService
{
    public function create(array $items, int $priority, string $note): void {}

    public function cancel(int $orderId): void {}

    public function archive(int $orderId): void {}
}
```

```php
use Eerzho\Instrumentation\Class\ClassInstrumentation;

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
