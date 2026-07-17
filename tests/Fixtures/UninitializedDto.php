<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\TraceProperties;

#[TraceProperties]
final class UninitializedDto
{
    public int $ready; // typed, no default — uninitialized until set

    public function __construct(
        public string $name,
    ) {
    }
}
