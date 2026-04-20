<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Class\Tests\Fixtures;

final class WithoutTraceableClass
{
    public function doSomething(): void
    {
    }
}
