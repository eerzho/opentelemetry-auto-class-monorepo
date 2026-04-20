<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Class\Tests\Fixtures;

use OpenTelemetry\Contrib\Instrumentation\Class\Attribute\Traceable;

#[Traceable]
final class MixedArgument
{
    public function process(mixed $value): void
    {
    }
}
