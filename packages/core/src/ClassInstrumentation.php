<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class;

use BackedEnum;
use DateTimeInterface;
use Eerzho\Instrumentation\Class\Attribute\TraceProperties;
use JsonException;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SemConv\Attributes\CodeAttributes;
use OpenTelemetry\SemConv\Version;
use ReflectionObject;
use ReflectionProperty;
use Throwable;

use function array_key_exists;
use function assert;
use function get_class;
use function gettype;
use function in_array;
use function is_array;
use function is_object;
use function is_scalar;
use function json_encode;
use function method_exists;
use function OpenTelemetry\Instrumentation\hook;
use function spl_object_id;

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
                        foreach (self::serialize($name, $params[$position]) as $key => $value) {
                            $builder->setAttribute($key, $value);
                        }
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

    /**
     * @param array<int, true> $seen
     *
     * @return array<string, bool|float|int|string>
     */
    private static function serialize(string $key, mixed $value, array $seen = []): array
    {
        if (is_scalar($value)) {
            return [$key => $value];
        }

        if ($value === null) {
            return [$key => 'null'];
        }

        if ($value instanceof BackedEnum) {
            return [$key => $value->value];
        }

        if ($value instanceof DateTimeInterface) {
            return [$key => $value->format(DateTimeInterface::RFC3339_EXTENDED)];
        }

        if (is_object($value)) {
            try {
                $reflection = new ReflectionObject($value);
                $attributes = $reflection->getAttributes(TraceProperties::class);

                if ($attributes !== []) {
                    $id = spl_object_id($value);
                    if (array_key_exists($id, $seen)) {
                        return [$key => get_class($value)];
                    }
                    $seen[$id] = true;

                    $attribute = $attributes[0]->newInstance();
                    assert($attribute instanceof TraceProperties);

                    $result = [];
                    foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                        $name = $property->getName();
                        if (!self::isAllowed($name, $attribute->include, $attribute->exclude)) {
                            continue;
                        }
                        if (!$property->isInitialized($value)) {
                            $result[$key . '.' . $name] = 'uninitialized';

                            continue;
                        }
                        $result += self::serialize($key . '.' . $name, $property->getValue($value), $seen);
                    }

                    return $result;
                }

                if (method_exists($value, '__toString')) {
                    return [$key => (string) $value];
                }
            } catch (Throwable) {
            }

            return [$key => get_class($value)];
        }

        if (is_array($value)) {
            try {
                return [$key => (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)];
            } catch (JsonException) {
            }
        }

        return [$key => gettype($value)];
    }

    /**
     * @param list<string> $include
     * @param list<string> $exclude
     */
    private static function isAllowed(string $name, array $include, array $exclude): bool
    {
        if ($include !== [] && !in_array($name, $include, true)) {
            return false;
        }

        return !in_array($name, $exclude, true);
    }
}
