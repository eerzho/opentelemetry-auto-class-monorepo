<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\Traceable;

#[Traceable]
abstract class TraceableAbstractClass
{
    public function doSomething(): void
    {
    }
}
