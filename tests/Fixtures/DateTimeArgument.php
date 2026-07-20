<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use DateTimeInterface;
use Eerzho\Instrumentation\Class\Attribute\Trace;
use Eerzho\Instrumentation\Class\Attribute\TraceArguments;

#[Trace]
final class DateTimeArgument
{
    #[TraceArguments]
    public function process(DateTimeInterface $at): void
    {
    }
}
