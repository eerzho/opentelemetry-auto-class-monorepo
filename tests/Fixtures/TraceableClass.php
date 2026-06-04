<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\Traceable;

#[Traceable]
final class TraceableClass
{
    public function greet(string $name): void
    {
    }

    public function add(int $a, int $b): int
    {
        return $a + $b;
    }
}
