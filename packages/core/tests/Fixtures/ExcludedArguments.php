<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Class\Tests\Fixtures;

use OpenTelemetry\Contrib\Instrumentation\Class\Attribute\Arguments;
use OpenTelemetry\Contrib\Instrumentation\Class\Attribute\Traceable;

#[Traceable]
final class ExcludedArguments
{
    #[Arguments(exclude: ['second'])]
    public function process(string $first, string $second, string $third): void
    {
    }
}
