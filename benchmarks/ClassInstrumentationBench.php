<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Benchmarks;

use Eerzho\Instrumentation\Class\AttributeScanner;
use Eerzho\Instrumentation\Class\ClassInstrumentation;
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
