<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Class;

use OpenTelemetry\Contrib\Instrumentation\Class\Attribute\Arguments;
use OpenTelemetry\Contrib\Instrumentation\Class\Attribute\Traceable;
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
        $attribute = self::findTraceable($class);
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
        $attribute = self::findArguments($method);
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
    private static function findTraceable(ReflectionClass $class): ?Traceable
    {
        $attributes = $class->getAttributes(Traceable::class);
        $instance = $attributes !== [] ? $attributes[0]->newInstance() : null;

        assert($instance instanceof Traceable || $instance === null);

        return $instance;
    }

    private static function findArguments(ReflectionMethod $method): ?Arguments
    {
        $attributes = $method->getAttributes(Arguments::class);
        $instance = $attributes !== [] ? $attributes[0]->newInstance() : null;

        assert($instance instanceof Arguments || $instance === null);

        return $instance;
    }
}
