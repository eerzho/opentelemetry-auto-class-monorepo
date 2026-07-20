<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use DateTimeInterface;
use Eerzho\Instrumentation\Class\Attribute\Trace;
use Eerzho\Instrumentation\Class\Attribute\TraceMethod;

#[Trace]
final class DateTimeArgument
{
    #[TraceMethod]
    public function process(DateTimeInterface $at): void
    {
    }
}
