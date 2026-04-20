<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Class\Benchmarks;

use OpenTelemetry\Contrib\Instrumentation\Class\Benchmarks\Fixtures\BenchStatus;
use OpenTelemetry\Contrib\Instrumentation\Class\Benchmarks\Fixtures\BenchStringable;
use OpenTelemetry\Contrib\Instrumentation\Class\ClassInstrumentation;
use PhpBench\Attributes as Bench;
use stdClass;

use function count;

#[Bench\BeforeMethods('setUp')]
#[Bench\Revs(1000)]
#[Bench\Iterations(5)]
class RuntimeMixedBench
{
    private object $service;
    /** @var list<mixed> */
    private array $values;

    /**
     * @param array{count: int} $params
     */
    public function setUp(array $params): void
    {
        $count = $params['count'];
        $className = "RuntimeMixedService{$count}";

        if (!class_exists($className)) {
            $paramsCode = [];
            for ($i = 0; $i < $count; $i++) {
                $paramsCode[] = "mixed \$p{$i}";
            }

            eval("class {$className} { public function execute(" . implode(', ', $paramsCode) . '): void {} }');
        }

        $map = [];
        for ($i = 0; $i < $count; $i++) {
            $map["p{$i}"] = $i;
        }

        ClassInstrumentation::register([
            $className => ['execute' => $map],
        ]);

        $this->service = new $className();
        $this->values = self::buildValues($count);
    }

    /**
     * @return iterable<string, array{count: int}>
     */
    public function provideArgCounts(): iterable
    {
        yield '10 args' => ['count' => 10];
        yield '50 args' => ['count' => 50];
        yield '100 args' => ['count' => 100];
    }

    /**
     * @param array{count: int} $params
     */
    #[Bench\ParamProviders('provideArgCounts')]
    public function benchMixed(array $params): void
    {
        $this->service->execute(...$this->values);
    }

    /**
     * @return list<mixed>
     */
    private static function buildValues(int $count): array
    {
        $prototypes = [
            'hello',
            42,
            true,
            3.14,
            null,
            BenchStatus::Active,
            new BenchStringable(),
            new stdClass(),
            ['a' => 1, 'b' => 2],
            [NAN],
            fopen('php://memory', 'r'),
        ];

        $values = [];
        for ($i = 0; $i < $count; $i++) {
            $values[] = $prototypes[$i % count($prototypes)];
        }

        return $values;
    }
}
