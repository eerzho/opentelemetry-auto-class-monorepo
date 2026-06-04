<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Benchmarks\Fixtures;

use Stringable;

final class BenchStringable implements Stringable
{
    public function __toString(): string
    {
        return 'hello';
    }
}
