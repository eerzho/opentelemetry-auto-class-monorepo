<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\Traceable;

#[Traceable]
final class MultipleArguments
{
    public function execute(string $first, int $second, bool $third, float $fourth): void
    {
    }
}
