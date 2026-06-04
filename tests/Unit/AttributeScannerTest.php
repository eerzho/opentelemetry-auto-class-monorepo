<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Unit;

use Eerzho\Instrumentation\Class\Attribute\Arguments;
use Eerzho\Instrumentation\Class\Attribute\Traceable;
use Eerzho\Instrumentation\Class\AttributeScanner;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ArgumentsWithoutTraceable;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ExcludedArguments;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ExcludedMethods;
use Eerzho\Instrumentation\Class\Tests\Fixtures\MixedVisibility;
use Eerzho\Instrumentation\Class\Tests\Fixtures\MultipleArguments;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TraceableAbstractClass;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TraceableClass;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TraceableEnum;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TraceableInterface;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TraceableTrait;
use Eerzho\Instrumentation\Class\Tests\Fixtures\WithoutTraceableClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * @internal
 */
#[CoversClass(Traceable::class)]
#[CoversClass(Arguments::class)]
#[CoversClass(AttributeScanner::class)]
final class AttributeScannerTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    public function testScanEmptyInput(): void
    {
        self::assertSame([], AttributeScanner::scan([]));
    }

    /**
     * @throws ReflectionException
     */
    public function testScanClassWithoutTraceable(): void
    {
        self::assertSame([], AttributeScanner::scan([WithoutTraceableClass::class]));
    }

    /**
     * @throws ReflectionException
     */
    public function testScanClassWithArgumentsButWithoutTraceable(): void
    {
        self::assertSame([], AttributeScanner::scan([ArgumentsWithoutTraceable::class]));
    }

    /**
     * @throws ReflectionException
     */
    public function testScanAbstractClass(): void
    {
        self::assertSame([], AttributeScanner::scan([TraceableAbstractClass::class]));
    }

    /**
     * @throws ReflectionException
     */
    public function testScanInterface(): void
    {
        self::assertSame([], AttributeScanner::scan([TraceableInterface::class]));
    }

    /**
     * @throws ReflectionException
     */
    public function testScanTrait(): void
    {
        self::assertSame([], AttributeScanner::scan([TraceableTrait::class]));
    }

    /**
     * @throws ReflectionException
     */
    public function testScanEnum(): void
    {
        self::assertSame([], AttributeScanner::scan([TraceableEnum::class]));
    }

    /**
     * @throws ReflectionException
     */
    public function testScanPublicMethods(): void
    {
        $result = AttributeScanner::scan([TraceableClass::class]);

        self::assertSame([
            TraceableClass::class => [
                'greet' => ['name' => 0],
                'add' => ['a' => 0, 'b' => 1],
            ],
        ], $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testScanExcludedMethods(): void
    {
        $result = AttributeScanner::scan([ExcludedMethods::class]);

        self::assertSame([
            ExcludedMethods::class => [
                'visible' => [],
            ],
        ], $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testScanExcludedArguments(): void
    {
        $result = AttributeScanner::scan([ExcludedArguments::class]);

        self::assertSame([
            ExcludedArguments::class => [
                'process' => [
                    'first' => 0,
                    'third' => 2,
                ],
            ],
        ], $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testScanNonPublicMethods(): void
    {
        $result = AttributeScanner::scan([MixedVisibility::class]);

        self::assertSame([
            MixedVisibility::class => [
                'publicMethod' => [],
            ],
        ], $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testScanArgumentPositions(): void
    {
        $result = AttributeScanner::scan([MultipleArguments::class]);

        self::assertSame([
            MultipleArguments::class => [
                'execute' => [
                    'first' => 0,
                    'second' => 1,
                    'third' => 2,
                    'fourth' => 3,
                ],
            ],
        ], $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testScanPreservedArgumentPositions(): void
    {
        $result = AttributeScanner::scan([ExcludedArguments::class]);

        self::assertSame([
            ExcludedArguments::class => [
                'process' => [
                    'first' => 0,
                    'third' => 2,
                ],
            ],
        ], $result);
    }
}
