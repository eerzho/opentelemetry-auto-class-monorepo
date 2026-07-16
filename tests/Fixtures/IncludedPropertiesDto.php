<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\TraceProperties;

#[TraceProperties(include: ['id'])]
final class IncludedPropertiesDto
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}
