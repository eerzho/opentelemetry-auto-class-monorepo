<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Benchmarks;

trait ClassGenerator
{
    /** @var list<class-string> */
    private array $classes;

    /**
     * @param array{count: int} $params
     */
    public function setUp(array $params): void
    {
        $this->classes = self::buildClasses($params['count'], 'ScanService');
    }

    /**
     * @return iterable<string, array{count: int}>
     */
    public function provideClassCounts(): iterable
    {
        yield '10 classes' => ['count' => 10];
        yield '100 classes' => ['count' => 100];
        yield '1000 classes' => ['count' => 1000];
    }

    /**
     * @return list<class-string>
     */
    private static function buildClasses(int $count, string $prefix): array
    {
        $classes = [];

        for ($i = 0; $i < $count; $i++) {
            $className = "{$prefix}{$i}";

            if (!class_exists($className)) {
                eval("
                    #[\\Eerzho\\Instrumentation\\Class\\Attribute\\Trace]
                    class {$className} {
                        public function findById(int \$id): void {}
                        public function findByEmail(string \$email): void {}
                        public function create(string \$name, string \$email, int \$age, array \$roles, \\stdClass \$meta): void {}
                        public function update(int \$id, string \$name, ?array \$options = null): void {}
                        public function delete(int \$id): void {}
                        public function import(array \$items, bool \$force, ?\\DateTimeImmutable \$scheduledAt = null): void {}
                        public function export(string \$format, array \$filters): string { return ''; }
                        #[\\Eerzho\\Instrumentation\\Class\\Attribute\\TraceMethod(exclude: ['password', 'token'])]
                        public function authenticate(string \$login, string \$password, string \$token): bool { return true; }
                    }
                ");
            }

            /** @var class-string $className */
            $classes[] = $className;
        }

        return $classes;
    }
}
