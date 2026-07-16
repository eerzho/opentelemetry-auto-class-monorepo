<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\Trace;
use Eerzho\Instrumentation\Class\Attribute\TraceArguments;

#[Trace]
final class IncludedArguments
{
    #[TraceArguments(include: ['first', 'third'])]
    public function process(string $first, string $second, string $third): void
    {
    }
}
