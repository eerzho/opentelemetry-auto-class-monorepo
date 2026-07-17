# opentelemetry-auto-class

[![Version](https://img.shields.io/packagist/v/eerzho/opentelemetry-auto-class)](https://packagist.org/packages/eerzho/opentelemetry-auto-class)
[![Downloads](https://img.shields.io/packagist/dt/eerzho/opentelemetry-auto-class)](https://packagist.org/packages/eerzho/opentelemetry-auto-class)
[![PHP](https://img.shields.io/packagist/dependency-v/eerzho/opentelemetry-auto-class/php)](https://packagist.org/packages/eerzho/opentelemetry-auto-class)
[![License](https://img.shields.io/packagist/l/eerzho/opentelemetry-auto-class)](https://packagist.org/packages/eerzho/opentelemetry-auto-class)

One tag, full visibility — every method call in your PHP app shows up in your traces, no boilerplate, no framework required.

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
    public function pay(int $orderId, string $card, Address $address): void {}
    public function healthCheck(): bool {}
}

class Address
{
    public function __construct(public string $city, public string $zip) {}
}
```

### 1. `#[Trace]` — a span per method

```php
use Eerzho\Instrumentation\Class\Attribute\Trace;

#[Trace(exclude: ['healthCheck'])]   // trace every public method but healthCheck
// #[Trace(include: ['pay'])]        // or: only pay
class OrderService
{
    public function pay(int $orderId, string $card, Address $address): void {}
    public function healthCheck(): bool {}
}
```

### 2. `#[TraceArguments]` — pick the captured arguments

```php
use Eerzho\Instrumentation\Class\Attribute\TraceArguments;

#[TraceArguments(exclude: ['card'])]       // capture every arg but card
// #[TraceArguments(include: ['orderId'])] // or: only orderId
public function pay(int $orderId, string $card, Address $address): void {}
```

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
        'pay'    => ['orderId' => 0],  // trace, capture orderId only
        'cancel' => [],                // trace, capture nothing
        // methods not listed are not traced
    ],
]);
```

Each entry maps a class → its methods → each method's arguments (`name => position`). Anything not listed is not traced or not captured.

## Traced output

### Selecting what to trace

`include` and `exclude` behave the same wherever they appear — `#[Trace]`, `#[TraceArguments]`, `#[TraceProperties]`:

| `include` | `exclude` | Result                                    |
|-----------|-----------|-------------------------------------------|
| `[]`      | `[]`      | Everything (default)                      |
| `[a, b]`  | `[]`      | Only `a` and `b`                          |
| `[]`      | `[a]`     | Everything except `a`                     |
| `[a, b]`  | `[b]`     | Only `a` — **`exclude` wins on conflict** |

An empty `include` means "no allowlist" (everything), **not** "nothing".

Only **public** methods are traced and only **public** properties are expanded — protected and private are ignored.

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

- Each public property becomes its own attribute, keyed `argument.property` (e.g. `address.city`).
- **Recursive** and unbounded — a nested property keeps expanding while its class also has `#[TraceProperties]`; otherwise it falls back to the rules above.
- An uninitialized typed property is recorded as `"uninitialized"`.
- Circular references are broken — a repeated object degrades to its class name.
- Any reflection or `__toString()` failure falls back to the class name, so serialization never breaks the traced call. (A failed array encode falls back to `"array"`.)

### Span structure

Each traced call produces an `INTERNAL` span named `ClassName::methodName`, with:

| Attribute            | Value                             |
|----------------------|-----------------------------------|
| `code.function.name` | `ClassName::methodName`           |
| `code.file.path`     | File where the method is defined  |
| `code.line.number`   | Line number of the method         |
| Method arguments     | Parameter name → serialized value |

If the method throws, the span records an `exception` event and its status is set to `ERROR`:

| Event attribute        | Value                |
|------------------------|----------------------|
| `exception.type`       | Exception class name |
| `exception.message`    | Exception message    |
| `exception.stacktrace` | Full stack trace     |

## How it works

`AttributeScanner::scan()` reflects over the classes you pass it:

1. Skips abstract classes, interfaces, traits, and enums.
2. Reads `#[Trace]` on the class — no attribute, no instrumentation.
3. Collects the public methods allowed by `include` / `exclude`, and for each the arguments allowed by `#[TraceArguments]`.

It returns a `class → method → {argument: position}` map. `ClassInstrumentation::register()` then installs an `ext-opentelemetry` hook on every mapped method:

- **pre** — opens the span and attaches the `code.*` attributes and serialized arguments.
- **post** — records the exception and sets `ERROR` status if the method threw, then ends the span.

## Disabling instrumentation

```bash
OTEL_PHP_DISABLED_INSTRUMENTATIONS=class
```
