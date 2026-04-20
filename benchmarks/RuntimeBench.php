<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Class\Benchmarks;

use OpenTelemetry\Contrib\Instrumentation\Class\AttributeScanner;
use OpenTelemetry\Contrib\Instrumentation\Class\Benchmarks\Fixtures\ArrayArg;
use OpenTelemetry\Contrib\Instrumentation\Class\Benchmarks\Fixtures\BackedEnumArg;
use OpenTelemetry\Contrib\Instrumentation\Class\Benchmarks\Fixtures\BenchStatus;
use OpenTelemetry\Contrib\Instrumentation\Class\Benchmarks\Fixtures\BenchStringable;
use OpenTelemetry\Contrib\Instrumentation\Class\Benchmarks\Fixtures\MixedArgs;
use OpenTelemetry\Contrib\Instrumentation\Class\Benchmarks\Fixtures\NoArgs;
use OpenTelemetry\Contrib\Instrumentation\Class\Benchmarks\Fixtures\NullArg;
use OpenTelemetry\Contrib\Instrumentation\Class\Benchmarks\Fixtures\ObjectNoToString;
use OpenTelemetry\Contrib\Instrumentation\Class\Benchmarks\Fixtures\ObjectToString;
use OpenTelemetry\Contrib\Instrumentation\Class\Benchmarks\Fixtures\ResourceArg;
use OpenTelemetry\Contrib\Instrumentation\Class\Benchmarks\Fixtures\Scalars;
use OpenTelemetry\Contrib\Instrumentation\Class\ClassInstrumentation;
use PhpBench\Attributes as Bench;
use ReflectionException;
use stdClass;

#[Bench\BeforeMethods('setUp')]
#[Bench\Revs(10000)]
#[Bench\Iterations(5)]
class RuntimeBench
{
    private BenchStringable $stringableObj;
    private stdClass $plainObj;
    /** @var resource */
    private mixed $resourceHandle;

    /**
     * @throws ReflectionException
     */
    public function setUp(): void
    {
        $map = AttributeScanner::scan([
            NoArgs::class,
            Scalars::class,
            NullArg::class,
            BackedEnumArg::class,
            ObjectToString::class,
            ObjectNoToString::class,
            ArrayArg::class,
            ResourceArg::class,
            MixedArgs::class,
        ]);

        ClassInstrumentation::register($map);

        $this->stringableObj = new BenchStringable();
        $this->plainObj = new stdClass();
        $this->resourceHandle = fopen('php://memory', 'r');
    }

    public function benchNoArgs(): void
    {
        (new NoArgs())->execute();
    }

    public function benchScalars(): void
    {
        (new Scalars())->execute('Alice', 30, true, 9.5);
    }

    public function benchNull(): void
    {
        (new NullArg())->execute(null);
    }

    public function benchBackedEnum(): void
    {
        (new BackedEnumArg())->execute(BenchStatus::Active);
    }

    public function benchObjectToString(): void
    {
        (new ObjectToString())->execute($this->stringableObj);
    }

    public function benchObjectNoToString(): void
    {
        (new ObjectNoToString())->execute($this->plainObj);
    }

    public function benchArray(): void
    {
        (new ArrayArg())->execute(['a' => 1, 'b' => 2, 'c' => 3]);
    }

    public function benchArrayJsonException(): void
    {
        (new ArrayArg())->execute([NAN]);
    }

    public function benchResource(): void
    {
        (new ResourceArg())->execute($this->resourceHandle);
    }

    public function benchMixed(): void
    {
        (new MixedArgs())->execute('Alice', 30, $this->plainObj, ['a', 'b'], true);
    }
}
