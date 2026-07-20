<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\Trace;
use Eerzho\Instrumentation\Class\Attribute\TraceArguments;

#[Trace]
final class TracedClass
{
    #[TraceArguments]
    public function greet(string $name): void
    {
    }

    #[TraceArguments]
    public function add(int $a, int $b): int
    {
        return $a + $b;
    }
}
