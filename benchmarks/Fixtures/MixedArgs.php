<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Benchmarks\Fixtures;

final class MixedArgs
{
    public function execute(string $name, int $count, object $item, array $tags, bool $active): void
    {
    }
}
