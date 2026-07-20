# opentelemetry-auto-class

[![Version](https://img.shields.io/packagist/v/eerzho/opentelemetry-auto-class)](https://packagist.org/packages/eerzho/opentelemetry-auto-class)
[![Downloads](https://img.shields.io/packagist/dt/eerzho/opentelemetry-auto-class)](https://packagist.org/packages/eerzho/opentelemetry-auto-class)
[![PHP](https://img.shields.io/packagist/dependency-v/eerzho/opentelemetry-auto-class/php)](https://packagist.org/packages/eerzho/opentelemetry-auto-class)
[![License](https://img.shields.io/packagist/l/eerzho/opentelemetry-auto-class)](https://packagist.org/packages/eerzho/opentelemetry-auto-class)

Trace what your methods received, returned, and threw — without writing a single span. No framework required.

This is a read-only sub-split. Please open issues and pull requests in the [monorepo](https://github.com/eerzho/opentelemetry-auto-class-monorepo).

The framework-agnostic engine — the [Laravel](https://github.com/eerzho/opentelemetry-auto-class-laravel) and [Symfony](https://github.com/eerzho/opentelemetry-auto-class-symfony) integrations build on it to discover and register your classes automatically.

## Installation

```bash
composer require eerzho/opentelemetry-auto-class
```

Requirements:
- [ext-opentelemetry](https://opentelemetry.io/docs/zero-code/php/)
- PHP 8.2+

## Usage

Start with a plain service — we'll add tracing to it one attribute at a time:

```php
namespace App\Service;

class OrderService
{
    public function pay(int $orderId, string $card, Address $address): string {}
    public function healthCheck(): bool {}
}

class Address
{
    public function __construct(public string $city, public string $zip) {}
}
```

### 1. `#[Trace]` — mark a class for tracing

```php
use Eerzho\Instrumentation\Class\Attribute\Trace;

#[Trace]   // mark the class for tracing
class OrderService
{
    public function pay(int $orderId, string $card, Address $address): string {}
    public function healthCheck(): bool {}
}
```

### 2. `#[TraceMethod]` — trace a method

```php
use Eerzho\Instrumentation\Class\Attribute\TraceMethod;

#[TraceMethod(exclude: ['card'])]       // capture every arg but card
// #[TraceMethod(include: ['orderId'])] // or: only orderId
public function pay(int $orderId, string $card, Address $address): string {}
```

A method gets a span with `#[TraceMethod]`. By default, it captures the arguments and the return value, and records exceptions. Turn each off with `arguments: false`, `return: false`, or `exception: false` (a disabled exception still sets the span status to `ERROR`, only its event is omitted).

### 3. `#[TraceProperties]` — expand an object argument

```php
use Eerzho\Instrumentation\Class\Attribute\TraceProperties;

#[TraceProperties(exclude: ['zip'])]     // expand every prop but zip
// #[TraceProperties(include: ['city'])] // or: only city
class Address
{
    public function __construct(public string $city, public string $zip) {}
}
```

### 4. Register

Once the attributes are in place, scan the classes and register the hooks — once, at bootstrap:

```php
use Eerzho\Instrumentation\Class\AttributeScanner;
use Eerzho\Instrumentation\Class\ClassInstrumentation;

ClassInstrumentation::register(AttributeScanner::scan([OrderService::class]));
```

### Manual registration

Skip `AttributeScanner` and hand `register()` the method map directly:

```php
ClassInstrumentation::register([
    OrderService::class => [
        'pay' => [
            'arguments' => ['orderId' => 0],  // name => position
            'return'    => true,
            'exception' => true,
        ],
        'cancel' => ['arguments' => []],      // trace, capture nothing
        // methods not listed are not traced
    ],
]);
```

Each method maps to its `arguments` (`name => position`) plus the `return` and `exception` flags. Missing keys default to off. Anything not listed is not traced.

## Traced output

### Selecting what to trace

`include` and `exclude` behave the same wherever they appear — `#[TraceMethod]` (arguments) and `#[TraceProperties]` (properties):

| `include` | `exclude` | Result                                    |
|-----------|-----------|-------------------------------------------|
| `[]`      | `[]`      | Everything (default)                      |
| `[a, b]`  | `[]`      | Only `a` and `b`                          |
| `[]`      | `[a]`     | Everything except `a`                     |
| `[a, b]`  | `[b]`     | Only `a` — **`exclude` wins on conflict** |

An empty `include` means "no allowlist" (everything), **not** "nothing".

All properties are expanded — use `exclude` to drop sensitive ones (tokens, hashes).

### Argument serialization

Each captured argument is serialized to a span-compatible value:

| Type                                                   | Result                     |
|--------------------------------------------------------|----------------------------|
| `string`, `int`, `float`, `bool`                       | As-is                      |
| `null`                                                 | `"null"`                   |
| `BackedEnum`                                           | Backing value              |
| `DateTimeInterface`                                    | RFC3339 with milliseconds  |
| Object with `#[TraceProperties]`                       | Expanded properties        |
| Object with `__toString()`                             | String cast                |
| Object without `#[TraceProperties]` and `__toString()` | Class name (FQCN)          |
| `array`                                                | JSON string                |
| Other (`resource`, ...)                                | `gettype()` result         |

Object expansion via `#[TraceProperties]`:

- Each property becomes its own attribute, keyed `code.argument.{name}.{property}` (e.g. `code.argument.address.city`).
- **Recursive** and unbounded — a nested property keeps expanding while its class also has `#[TraceProperties]`; otherwise it falls back to the rules above.
- An uninitialized typed property is recorded as `"uninitialized"`.
- Circular references are broken — a repeated object degrades to its class name.
- Any reflection or `__toString()` failure falls back to the class name, so serialization never breaks the traced call. (A failed array encode falls back to `"array"`.)

### Span structure

Each traced call produces an `INTERNAL` span named `ClassName::methodName`, with:

| Attribute              | Value                                                             |
|------------------------|-------------------------------------------------------------------|
| `code.function.name`   | `ClassName::methodName`                                           |
| `code.file.path`       | File where the method is defined                                  |
| `code.line.number`     | Line number of the method                                         |
| `code.return`          | Return value, serialized the same way                             |
| `code.argument.{name}` | Method argument, keyed by parameter name, serialized the same way |

If the method throws, the span records an `exception` event and its status is set to `ERROR`:

| Event attribute        | Value                |
|------------------------|----------------------|
| `exception.type`       | Exception class name |
| `exception.message`    | Exception message    |
| `exception.stacktrace` | Full stack trace     |

With `exception: false` the status still becomes `ERROR`, but the event above is omitted.

## How it works

`AttributeScanner::scan()` reflects over the classes you pass it:

1. Skips abstract classes, interfaces, traits, and enums.
2. Reads `#[Trace]` on the class — no attribute, no instrumentation.
3. Collects every method carrying `#[TraceMethod]`, and for each the arguments, return value, and exception recording it configures.

It returns a `class → method → {arguments, return, exception}` map. `ClassInstrumentation::register()` then installs an `ext-opentelemetry` hook on every mapped method:

- **pre** — opens the span and attaches the `code.*` attributes and serialized arguments.
- **post** — captures the return value on success, records the exception and sets `ERROR` status on failure, then ends the span.

## Disabling instrumentation

```bash
OTEL_PHP_DISABLED_INSTRUMENTATIONS=class
```
