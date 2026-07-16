<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

// No #[TraceProperties] — must fall back to class name when expanded as a property.
final class PlainValue
{
    public function __construct(
        public string $data,
    ) {
    }
}
