<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Unit;

use Eerzho\Instrumentation\Class\Attribute\Trace;
use Eerzho\Instrumentation\Class\Attribute\TraceArguments;
use Eerzho\Instrumentation\Class\AttributeScanner;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ExcludedArguments;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ExcludedMethods;
use Eerzho\Instrumentation\Class\Tests\Fixtures\IncludedArguments;
use Eerzho\Instrumentation\Class\Tests\Fixtures\IncludedMethods;
use Eerzho\Instrumentation\Class\Tests\Fixtures\MixedVisibility;
use Eerzho\Instrumentation\Class\Tests\Fixtures\MultipleArguments;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TraceArgumentsWithoutTrace;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TracedAbstractClass;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TracedClass;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TracedEnum;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TracedInterface;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TracedTrait;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TraceWithoutArguments;
use Eerzho\Instrumentation\Class\Tests\Fixtures\WithoutTraceClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * @internal
 */
#[CoversClass(Trace::class)]
#[CoversClass(TraceArguments::class)]
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
    public function testScanClassWithoutTrace(): void
    {
        self::assertSame([], AttributeScanner::scan([WithoutTraceClass::class]));
    }

    /**
     * @throws ReflectionException
     */
    public function testScanClassWithArgumentsButWithoutTrace(): void
    {
        self::assertSame([], AttributeScanner::scan([TraceArgumentsWithoutTrace::class]));
    }

    /**
     * @throws ReflectionException
     */
    public function testScanAbstractClass(): void
    {
        self::assertSame([], AttributeScanner::scan([TracedAbstractClass::class]));
    }

    /**
     * @throws ReflectionException
     */
    public function testScanInterface(): void
    {
        self::assertSame([], AttributeScanner::scan([TracedInterface::class]));
    }

    /**
     * @throws ReflectionException
     */
    public function testScanTrait(): void
    {
        self::assertSame([], AttributeScanner::scan([TracedTrait::class]));
    }

    /**
     * @throws ReflectionException
     */
    public function testScanEnum(): void
    {
        self::assertSame([], AttributeScanner::scan([TracedEnum::class]));
    }

    /**
     * @throws ReflectionException
     */
    public function testScanPublicMethods(): void
    {
        $result = AttributeScanner::scan([TracedClass::class]);

        self::assertSame([
            TracedClass::class => [
                'greet' => ['name' => 0],
                'add' => ['a' => 0, 'b' => 1],
            ],
        ], $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testScanMethodWithoutTraceArguments(): void
    {
        $result = AttributeScanner::scan([TraceWithoutArguments::class]);

        self::assertSame([
            TraceWithoutArguments::class => [
                'handle' => [],
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

    /**
     * @throws ReflectionException
     */
    public function testScanIncludedMethods(): void
    {
        $result = AttributeScanner::scan([IncludedMethods::class]);

        self::assertSame([
            IncludedMethods::class => [
                'visible' => [],
            ],
        ], $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testScanIncludedArguments(): void
    {
        $result = AttributeScanner::scan([IncludedArguments::class]);

        self::assertSame([
            IncludedArguments::class => [
                'process' => [
                    'first' => 0,
                    'third' => 2,
                ],
            ],
        ], $result);
    }
}
