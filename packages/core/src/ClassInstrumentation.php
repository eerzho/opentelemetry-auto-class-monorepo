<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class;

use BackedEnum;
use JsonException;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SemConv\Attributes\CodeAttributes;
use OpenTelemetry\SemConv\Version;
use Throwable;

use function array_key_exists;
use function get_class;
use function gettype;
use function is_array;
use function is_object;
use function is_scalar;
use function json_encode;
use function OpenTelemetry\Instrumentation\hook;

final class ClassInstrumentation
{
    public const NAME = 'class';

    /**
     * @param array<class-string, array<string, array<string, int>>> $classesMap
     */
    public static function register(array $classesMap): void
    {
        $instrumentation = new CachedInstrumentation(
            'io.opentelemetry.contrib.php.class',
            schemaUrl: Version::VERSION_1_32_0->url(),
        );

        foreach ($classesMap as $class => $methods) {
            foreach ($methods as $method => $arguments) {
                self::registerHook($instrumentation, $class, $method, $arguments);
            }
        }
    }

    /**
     * @param array<string, int> $arguments
     */
    private static function registerHook(
        CachedInstrumentation $instrumentation,
        string $class,
        string $method,
        array $arguments,
    ): void {
        $positionToName = array_flip($arguments);

        hook(
            $class,
            $method,
            pre: static function (
                mixed $instance,
                array $params,
                string $class,
                string $function,
                ?string $filename,
                ?int $lineno,
            ) use ($instrumentation, $positionToName): void {
                $builder = $instrumentation->tracer()
                    ->spanBuilder($class . '::' . $function)
                    ->setSpanKind(SpanKind::KIND_INTERNAL)
                    ->setAttribute(CodeAttributes::CODE_FUNCTION_NAME, $class . '::' . $function)
                    ->setAttribute(CodeAttributes::CODE_FILE_PATH, $filename)
                    ->setAttribute(CodeAttributes::CODE_LINE_NUMBER, $lineno);

                foreach ($positionToName as $position => $name) {
                    if (array_key_exists($position, $params)) {
                        $builder->setAttribute($name, self::serializeValue($params[$position]));
                    }
                }

                $span = $builder->startSpan();
                Context::storage()->attach($span->storeInContext(Context::getCurrent()));
            },
            post: static function (
                mixed $instance,
                array $params,
                mixed $returnValue,
                ?Throwable $exception,
            ): void {
                $scope = Context::storage()->scope();
                if ($scope === null) {
                    return;
                }

                $scope->detach();
                $span = Span::fromContext($scope->context());

                if ($exception !== null) {
                    $span->recordException($exception);
                    $span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
                }

                $span->end();
            },
        );
    }

    private static function serializeValue(mixed $value): bool|float|int|string
    {
        if (is_scalar($value)) {
            return $value;
        }

        if ($value === null) {
            return 'null';
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if (is_object($value)) {
            return method_exists($value, '__toString') ? (string) $value : get_class($value);
        }

        if (is_array($value)) {
            try {
                return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                return 'array';
            }
        }

        return gettype($value);
    }
}
