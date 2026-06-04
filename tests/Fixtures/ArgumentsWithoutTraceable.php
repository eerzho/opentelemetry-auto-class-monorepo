<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\Arguments;

final class ArgumentsWithoutTraceable
{
    #[Arguments(exclude: ['password'])]
    public function login(string $email, string $password): void
    {
    }
}
