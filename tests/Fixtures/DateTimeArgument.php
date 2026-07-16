<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use DateTimeInterface;
use Eerzho\Instrumentation\Class\Attribute\Trace;

#[Trace]
final class DateTimeArgument
{
    public function process(DateTimeInterface $at): void
    {
    }
}
