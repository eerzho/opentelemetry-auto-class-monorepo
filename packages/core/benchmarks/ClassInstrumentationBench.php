<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Class\Benchmarks;

use OpenTelemetry\Contrib\Instrumentation\Class\AttributeScanner;
use OpenTelemetry\Contrib\Instrumentation\Class\ClassInstrumentation;
use PhpBench\Attributes as Bench;
use ReflectionException;

#[Bench\BeforeMethods('setUp')]
class ClassInstrumentationBench
{
    use ClassGenerator;

    /**
     * @param array{count: int} $params
     *
     * @throws ReflectionException
     */
    #[Bench\ParamProviders('provideClassCounts')]
    #[Bench\Revs(10)]
    #[Bench\Iterations(5)]
    public function benchRegister(array $params): void
    {
        $map = AttributeScanner::scan($this->classes);
        ClassInstrumentation::register($map);
    }
}
