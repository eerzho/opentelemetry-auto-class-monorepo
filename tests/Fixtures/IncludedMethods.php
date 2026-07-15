<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\Trace;

#[Trace(include: ['visible', 'also'], exclude: ['also'])]
final class IncludedMethods
{
    public function visible(): void
    {
    }

    public function also(): void
    {
    }

    public function hidden(): void
    {
    }
}
