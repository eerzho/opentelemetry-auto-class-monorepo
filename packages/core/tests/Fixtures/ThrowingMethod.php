<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Class\Tests\Fixtures;

use OpenTelemetry\Contrib\Instrumentation\Class\Attribute\Traceable;
use RuntimeException;

#[Traceable]
final class ThrowingMethod
{
    public function execute(): void
    {
        throw new RuntimeException('something went wrong');
    }
}
