<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Class\Tests\Fixtures;

use OpenTelemetry\Contrib\Instrumentation\Class\Attribute\Traceable;

#[Traceable]
final class MixedVisibility
{
    public function publicMethod(): void
    {
    }

    private function protectedMethod(): void
    {
    }

    private function privateMethod(): void
    {
    }
}
