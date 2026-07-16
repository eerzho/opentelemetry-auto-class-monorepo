<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class TraceProperties
{
    /**
     * @param list<string> $include
     * @param list<string> $exclude
     */
    public function __construct(
        public array $include = [],
        public array $exclude = [],
    ) {
    }
}
