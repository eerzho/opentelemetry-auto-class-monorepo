<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class;

use Eerzho\Instrumentation\Class\Attribute\Trace;
use Eerzho\Instrumentation\Class\Attribute\TraceArguments;
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
     * @return array<class-string, array<string, array<string, int>>>
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
     * @return array<string, array<string, int>>
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
     * @return array<string, array<string, int>>
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
                $methodsMap[$method->getName()] = self::scanArguments($method);
            }
        }

        return $methodsMap;
    }

    /**
     * @return array<string, int>
     */
    private static function scanArguments(ReflectionMethod $method): array
    {
        $attribute = self::findTraceArguments($method);
        if ($attribute === null) {
            return [];
        }

        $arguments = [];
        foreach ($method->getParameters() as $parameter) {
            $name = $parameter->getName();
            if (self::isAllowed($name, $attribute->include, $attribute->exclude)) {
                $arguments[$name] = $parameter->getPosition();
            }
        }

        return $arguments;
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

    private static function findTraceArguments(ReflectionMethod $method): ?TraceArguments
    {
        $attributes = $method->getAttributes(TraceArguments::class);
        $instance = $attributes !== [] ? $attributes[0]->newInstance() : null;

        assert($instance instanceof TraceArguments || $instance === null);

        return $instance;
    }
}
