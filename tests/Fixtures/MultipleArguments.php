<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\Trace;
use Eerzho\Instrumentation\Class\Attribute\TraceMethod;

#[Trace]
final class MultipleArguments
{
    #[TraceMethod]
    public function execute(string $first, int $second, bool $third, float $fourth): void
    {
    }
}
