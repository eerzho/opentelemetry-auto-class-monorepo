<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\Trace;
use Eerzho\Instrumentation\Class\Attribute\TraceMethod;

#[Trace]
final class ArgumentsDisabled
{
    #[TraceMethod(arguments: false)]
    public function process(string $value): void
    {
    }
}
