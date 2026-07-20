<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\Trace;
use Eerzho\Instrumentation\Class\Attribute\TraceMethod;

#[Trace]
final class ExcludedArguments
{
    #[TraceMethod(exclude: ['second'])]
    public function process(string $first, string $second, string $third): void
    {
    }
}
