<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Class\Benchmarks\Fixtures;

enum BenchStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
