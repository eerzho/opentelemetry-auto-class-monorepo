<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Class\Benchmarks\Fixtures;

final class ObjectNoToString
{
    public function execute(object $item): void
    {
    }
}
