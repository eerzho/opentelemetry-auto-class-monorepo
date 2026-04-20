<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Class\Tests\Fixtures;

use OpenTelemetry\Contrib\Instrumentation\Class\Attribute\Traceable;

#[Traceable]
interface TraceableInterface
{
    public function doSomething(): void;
}
