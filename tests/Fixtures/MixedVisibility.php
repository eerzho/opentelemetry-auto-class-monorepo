<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\Trace;
use Eerzho\Instrumentation\Class\Attribute\TraceMethod;

#[Trace]
class MixedVisibility
{
    #[TraceMethod]
    public function publicMethod(): void
    {
        $this->protectedMethod();
        $this->privateMethod();
    }

    #[TraceMethod]
    protected function protectedMethod(): void
    {
    }

    #[TraceMethod]
    private function privateMethod(): void
    {
    }
}
