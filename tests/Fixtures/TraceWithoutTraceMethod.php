<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\Trace;

#[Trace]
final class TraceWithoutTraceMethod
{
    public function handle(string $name): void
    {
    }
}
