<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\TraceProperties;

#[TraceProperties]
final class NodeDto
{
    public ?NodeDto $next = null;

    public function __construct(
        public int $id,
    ) {
    }
}
