<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Class\Tests\Fixtures;

use OpenTelemetry\Contrib\Instrumentation\Class\Attribute\Arguments;

final class ArgumentsWithoutTraceable
{
    #[Arguments(exclude: ['password'])]
    public function login(string $email, string $password): void
    {
    }
}
