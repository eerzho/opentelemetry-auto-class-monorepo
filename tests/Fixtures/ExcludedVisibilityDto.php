<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Fixtures;

use Eerzho\Instrumentation\Class\Attribute\TraceProperties;

#[TraceProperties(exclude: ['publicOne', 'protectedOne', 'privateOne'])]
final class ExcludedVisibilityDto
{
    protected string $protectedOne = 'protected-one';

    protected string $protectedTwo = 'protected-two';

    private string $privateOne = 'private-one';

    private string $privateTwo = 'private-two';

    public function __construct(
        public string $publicOne = 'public-one',
        public string $publicTwo = 'public-two',
    ) {
    }
}
