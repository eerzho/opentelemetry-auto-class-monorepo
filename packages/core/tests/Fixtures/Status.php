<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Class\Tests\Fixtures;

enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
