<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use RuntimeException;

// __toString() throws — serialization must degrade to the class name, never propagate.
final class ThrowingStringable
{
    public function __toString(): string
    {
        throw new RuntimeException('boom');
    }
}
