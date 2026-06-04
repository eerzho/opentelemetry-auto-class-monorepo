<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\Traceable;
use RuntimeException;

#[Traceable]
final class ThrowingMethod
{
    public function execute(): void
    {
        throw new RuntimeException('something went wrong');
    }
}
