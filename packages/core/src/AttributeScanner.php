<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class;

use Eerzho\Instrumentation\Class\Attribute\Trace;
use Eerzho\Instrumentation\Class\Attribute\TraceArguments;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

use function array_key_exists;
use function assert;

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
        $excludeMap = array_flip($attribute->exclude);

        $methodsMap = [];
        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (!array_key_exists($method->getName(), $excludeMap)) {
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
            $arguments = [];
            foreach ($method->getParameters() as $parameter) {
                $arguments[$parameter->getName()] = $parameter->getPosition();
            }

            return $arguments;
        }
        $excludeMap = array_flip($attribute->exclude);

        $arguments = [];
        foreach ($method->getParameters() as $parameter) {
            if (!array_key_exists($parameter->getName(), $excludeMap)) {
                $arguments[$parameter->getName()] = $parameter->getPosition();
            }
        }

        return $arguments;
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
