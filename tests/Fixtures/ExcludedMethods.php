<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Class\Tests\Fixtures;

use OpenTelemetry\Contrib\Instrumentation\Class\Attribute\Traceable;

#[Traceable(exclude: ['secret'])]
final class ExcludedMethods
{
    public function visible(): void
    {
    }

    public function secret(): void
    {
    }
}
