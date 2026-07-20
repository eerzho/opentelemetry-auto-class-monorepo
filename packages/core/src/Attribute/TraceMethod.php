<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class TraceMethod
{
    /**
     * @param list<string> $include
     * @param list<string> $exclude
     */
    public function __construct(
        public bool $arguments = true,
        public array $include = [],
        public array $exclude = [],
        public bool $return = true,
        public bool $exception = true,
    ) {
    }
}
