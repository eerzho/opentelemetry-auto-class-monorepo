<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\Trace;

#[Trace]
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
