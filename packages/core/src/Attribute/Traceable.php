<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Class\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Traceable
{
    /**
     * @param list<string> $exclude
     */
    public function __construct(
        public array $exclude = [],
    ) {
    }
}
