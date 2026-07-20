<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\Trace;
use Eerzho\Instrumentation\Class\Attribute\TraceMethod;
use RuntimeException;

#[Trace]
final class ExceptionDisabled
{
    #[TraceMethod(exception: false)]
    public function execute(): void
    {
        throw new RuntimeException('boom');
    }
}
