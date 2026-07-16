<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\TraceProperties;

#[TraceProperties]
final class WrapperDto
{
    public function __construct(
        public PlainValue $inner,
    ) {
    }
}
