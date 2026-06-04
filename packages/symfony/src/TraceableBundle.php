<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Symfony;

use Eerzho\Instrumentation\Class\AttributeScanner;
use Eerzho\Instrumentation\Class\ClassInstrumentation;
use Error;
use OpenTelemetry\SDK\Sdk;
use ReflectionException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use function class_exists;
use function extension_loaded;

final class TraceableBundle extends Bundle implements CompilerPassInterface
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass($this);
    }

    /**
     * @throws ReflectionException
     */
    public function process(ContainerBuilder $container): void
    {
        /** @var list<class-string> $classes */
        $classes = [];
        foreach ($container->getDefinitions() as $definition) {
            $class = $definition->getClass();
            if ($class === null) {
                continue;
            }

            try {
                if (class_exists($class)) {
                    $classes[] = $class;
                }
            } catch (Error) {
            }
        }
        $classesMap = AttributeScanner::scan($classes);
        $container->setParameter('otel.traceable.classes_map', $classesMap);
    }

    public function boot(): void
    {
        if (!extension_loaded('opentelemetry')) {
            return;
        }

        if (class_exists(Sdk::class) && Sdk::isInstrumentationDisabled(ClassInstrumentation::NAME)) {
            return;
        }

        if ($this->container === null) {
            return;
        }

        /** @var array<class-string, array<string, array<string, int>>> $classesMap */
        $classesMap = $this->container->getParameter('otel.traceable.classes_map');
        ClassInstrumentation::register($classesMap);
    }
}
