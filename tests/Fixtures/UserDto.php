<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\TraceProperties;

#[TraceProperties]
final class UserDto
{
    public function __construct(
        public int $id,
        public string $name,
        public AddressDto $address,
    ) {
    }
}
