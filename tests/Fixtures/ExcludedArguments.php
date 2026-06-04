<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\Arguments;
use Eerzho\Instrumentation\Class\Attribute\Traceable;

#[Traceable]
final class ExcludedArguments
{
    #[Arguments(exclude: ['second'])]
    public function process(string $first, string $second, string $third): void
    {
    }
}
