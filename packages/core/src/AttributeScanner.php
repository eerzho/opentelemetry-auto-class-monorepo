<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class;

use Eerzho\Instrumentation\Class\Attribute\Trace;
use Eerzho\Instrumentation\Class\Attribute\TraceMethod;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

use function assert;
use function in_array;

final class AttributeScanner
{
    /**
     * @param list<class-string> $classes
     *
     * @throws ReflectionException
     *
     * @return array<class-string, array<string, array{arguments: array<string, int>, return: bool, exception: bool}>>
     */
    public static function scan(array $classes): array
    {
        $classesMap = [];

        foreach ($classes as $class) {
            $methodsMap = self::scanClass($class);
            if ($methodsMap !== []) {
                $classesMap[$class] = $methodsMap;
            }
        }

        return $classesMap;
    }

    /**
     * @param class-string $class
     *
     * @throws ReflectionException
     *
     * @return array<string, array{arguments: array<string, int>, return: bool, exception: bool}>
     */
    private static function scanClass(string $class): array
    {
        $reflectionClass = new ReflectionClass($class);
        if (
            $reflectionClass->isAbstract()
            || $reflectionClass->isInterface()
            || $reflectionClass->isTrait()
            || $reflectionClass->isEnum()
        ) {
            return [];
        }

        return self::scanMethods($reflectionClass);
    }

    /**
     * @param ReflectionClass<object> $class
     *
     * @return array<string, array{arguments: array<string, int>, return: bool, exception: bool}>
     */
    private static function scanMethods(ReflectionClass $class): array
    {
        $attribute = self::findTrace($class);
        if ($attribute === null) {
            return [];
        }

        $methodsMap = [];
        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (self::isAllowed($method->getName(), $attribute->include, $attribute->exclude)) {
                $methodsMap[$method->getName()] = self::scanMethod($method);
            }
        }

        return $methodsMap;
    }

    /**
     * @return array{arguments: array<string, int>, return: bool, exception: bool}
     */
    private static function scanMethod(ReflectionMethod $method): array
    {
        $attribute = self::findTraceMethod($method);
        if ($attribute === null) {
            return ['arguments' => [], 'return' => false, 'exception' => false];
        }

        $arguments = [];
        if ($attribute->arguments) {
            foreach ($method->getParameters() as $parameter) {
                $name = $parameter->getName();
                if (self::isAllowed($name, $attribute->include, $attribute->exclude)) {
                    $arguments[$name] = $parameter->getPosition();
                }
            }
        }

        return [
            'arguments' => $arguments,
            'return' => $attribute->return,
            'exception' => $attribute->exception,
        ];
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

    /**
     * @param ReflectionClass<object> $class
     */
    private static function findTrace(ReflectionClass $class): ?Trace
    {
        $attributes = $class->getAttributes(Trace::class);
        $instance = $attributes !== [] ? $attributes[0]->newInstance() : null;

        assert($instance instanceof Trace || $instance === null);

        return $instance;
    }

    private static function findTraceMethod(ReflectionMethod $method): ?TraceMethod
    {
        $attributes = $method->getAttributes(TraceMethod::class);
        $instance = $attributes !== [] ? $attributes[0]->newInstance() : null;

        assert($instance instanceof TraceMethod || $instance === null);

        return $instance;
    }
}
