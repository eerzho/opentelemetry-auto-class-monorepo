<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\Traceable;

#[Traceable]
final class ArrayArgument
{
    public function process(array $items): void
    {
    }
}
