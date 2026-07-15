<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\Trace;

#[Trace(exclude: ['secret'])]
final class ExcludedMethods
{
    public function visible(): void
    {
    }

    public function secret(): void
    {
    }
}
