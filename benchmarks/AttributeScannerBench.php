<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Class\Benchmarks;

use OpenTelemetry\Contrib\Instrumentation\Class\AttributeScanner;
use PhpBench\Attributes as Bench;
use ReflectionException;

#[Bench\BeforeMethods('setUp')]
class AttributeScannerBench
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
    public function benchScan(array $params): void
    {
        AttributeScanner::scan($this->classes);
    }
}
