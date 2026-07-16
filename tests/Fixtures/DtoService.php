<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\Trace;

#[Trace]
final class DtoService
{
    public function expand(UserDto $user): void
    {
    }

    public function filtered(FilteredDto $dto): void
    {
    }

    public function cyclic(NodeDto $node): void
    {
    }

    public function wrapped(WrapperDto $wrapper): void
    {
    }

    public function uninitialized(UninitializedDto $dto): void
    {
    }
}
