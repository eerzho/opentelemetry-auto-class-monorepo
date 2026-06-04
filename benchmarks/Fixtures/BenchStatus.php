<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Benchmarks\Fixtures;

enum BenchStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
