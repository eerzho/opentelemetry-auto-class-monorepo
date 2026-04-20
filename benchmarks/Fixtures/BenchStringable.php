<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Class\Benchmarks\Fixtures;

use Stringable;

final class BenchStringable implements Stringable
{
    public function __toString(): string
    {
        return 'hello';
    }
}
