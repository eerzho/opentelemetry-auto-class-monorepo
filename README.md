# opentelemetry-auto-class-monorepo

[![CI](https://github.com/eerzho/opentelemetry-auto-class-monorepo/actions/workflows/ci.yml/badge.svg)](https://github.com/eerzho/opentelemetry-auto-class-monorepo/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/eerzho/opentelemetry-auto-class-monorepo/branch/main/graph/badge.svg)](https://codecov.io/gh/eerzho/opentelemetry-auto-class-monorepo)
[![PHP Version](https://img.shields.io/badge/php-≥8.2-blue)](https://github.com/eerzho/opentelemetry-auto-class-monorepo)
[![License](https://img.shields.io/badge/license-MIT-green)](https://github.com/eerzho/opentelemetry-auto-class-monorepo)

Monorepo for automatic OpenTelemetry tracing of PHP methods via the `#[Traceable]` attribute. Mark any class with the attribute — spans are created automatically using the `ext-opentelemetry` hook API, no manual instrumentation needed.

## Packages

| Package                                 | Docs                                 | Badges                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
|-----------------------------------------|--------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| eerzho/opentelemetry-auto-class         | [README](packages/core/README.md)    | [![Version](https://img.shields.io/packagist/v/eerzho/opentelemetry-auto-class)](https://packagist.org/packages/eerzho/opentelemetry-auto-class) [![Downloads](https://img.shields.io/packagist/dt/eerzho/opentelemetry-auto-class)](https://packagist.org/packages/eerzho/opentelemetry-auto-class) [![PHP](https://img.shields.io/packagist/dependency-v/eerzho/opentelemetry-auto-class/php)](https://packagist.org/packages/eerzho/opentelemetry-auto-class) [![License](https://img.shields.io/packagist/l/eerzho/opentelemetry-auto-class)](https://packagist.org/packages/eerzho/opentelemetry-auto-class)                                                                 |
| eerzho/opentelemetry-auto-class-laravel | [README](packages/laravel/README.md) | [![Version](https://img.shields.io/packagist/v/eerzho/opentelemetry-auto-class-laravel)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-laravel) [![Downloads](https://img.shields.io/packagist/dt/eerzho/opentelemetry-auto-class-laravel)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-laravel) [![PHP](https://img.shields.io/packagist/dependency-v/eerzho/opentelemetry-auto-class-laravel/php)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-laravel) [![License](https://img.shields.io/packagist/l/eerzho/opentelemetry-auto-class-laravel)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-laravel) |
| eerzho/opentelemetry-auto-class-symfony | [README](packages/symfony/README.md) | [![Version](https://img.shields.io/packagist/v/eerzho/opentelemetry-auto-class-symfony)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-symfony) [![Downloads](https://img.shields.io/packagist/dt/eerzho/opentelemetry-auto-class-symfony)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-symfony) [![PHP](https://img.shields.io/packagist/dependency-v/eerzho/opentelemetry-auto-class-symfony/php)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-symfony) [![License](https://img.shields.io/packagist/l/eerzho/opentelemetry-auto-class-symfony)](https://packagist.org/packages/eerzho/opentelemetry-auto-class-symfony) |

## Contributing

All commands run inside Docker. No local PHP or extensions required. Run `make help` to see all available commands.

```bash
# Install dependencies
make vendor

# Lint (CS Fixer + PHPStan)
make lint

# Run tests with coverage
make test

# Run benchmarks
make bench

# Run everything: vendor, lint, test, bench
make audit
```

Default PHP version is 8.2. Override with `PHP_VERSION` for any command:

```bash
make test PHP_VERSION=8.3
```

## License

[MIT](LICENSE)

