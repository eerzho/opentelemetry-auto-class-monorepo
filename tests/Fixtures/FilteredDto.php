<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\TraceProperties;

#[TraceProperties(exclude: ['token'])]
final class FilteredDto
{
    public function __construct(
        public string $email,
        public string $token,
    ) {
    }
}
