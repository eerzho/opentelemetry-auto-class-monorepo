<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Unit;

use Eerzho\Instrumentation\Class\AttributeScanner;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ArgumentsDisabled;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ExceptionDisabled;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ExcludedArguments;
use Eerzho\Instrumentation\Class\Tests\Fixtures\IncludedArguments;
use Eerzho\Instrumentation\Class\Tests\Fixtures\MixedVisibility;
use Eerzho\Instrumentation\Class\Tests\Fixtures\PartiallyTraced;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ReturnDisabled;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TracedAbstractClass;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TracedClass;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TracedEnum;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TracedInterface;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TracedTrait;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TraceMethodWithoutTrace;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TraceWithoutTraceMethod;
use Eerzho\Instrumentation\Class\Tests\Fixtures\WithoutTraceClass;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * @internal
 */
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
    public function testScanTraceMethodWithoutTrace(): void
    {
        self::assertSame([], AttributeScanner::scan([TraceMethodWithoutTrace::class]));
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
    public function testScanTracedMethods(): void
    {
        $result = AttributeScanner::scan([TracedClass::class]);

        self::assertSame([
            TracedClass::class => [
                'greet' => ['arguments' => ['name' => 0], 'return' => true, 'exception' => true],
                'add' => ['arguments' => ['a' => 0, 'b' => 1], 'return' => true, 'exception' => true],
            ],
        ], $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testScanTraceWithoutTraceMethod(): void
    {
        self::assertSame([], AttributeScanner::scan([TraceWithoutTraceMethod::class]));
    }

    /**
     * @throws ReflectionException
     */
    public function testScanOnlyTracedMethods(): void
    {
        $result = AttributeScanner::scan([PartiallyTraced::class]);

        self::assertSame([
            PartiallyTraced::class => [
                'traced' => ['arguments' => [], 'return' => true, 'exception' => true],
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
                'process' => ['arguments' => ['first' => 0, 'third' => 2], 'return' => true, 'exception' => true],
            ],
        ], $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testScanAllVisibilities(): void
    {
        $result = AttributeScanner::scan([MixedVisibility::class]);

        self::assertSame([
            MixedVisibility::class => [
                'publicMethod' => ['arguments' => [], 'return' => true, 'exception' => true],
                'protectedMethod' => ['arguments' => [], 'return' => true, 'exception' => true],
                'privateMethod' => ['arguments' => [], 'return' => true, 'exception' => true],
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
                'process' => ['arguments' => ['first' => 0, 'third' => 2], 'return' => true, 'exception' => true],
            ],
        ], $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testScanArgumentsDisabled(): void
    {
        $result = AttributeScanner::scan([ArgumentsDisabled::class]);

        self::assertSame([
            ArgumentsDisabled::class => [
                'process' => ['arguments' => [], 'return' => true, 'exception' => true],
            ],
        ], $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testScanReturnDisabled(): void
    {
        $result = AttributeScanner::scan([ReturnDisabled::class]);

        self::assertSame([
            ReturnDisabled::class => [
                'compute' => ['arguments' => [], 'return' => false, 'exception' => true],
            ],
        ], $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testScanExceptionDisabled(): void
    {
        $result = AttributeScanner::scan([ExceptionDisabled::class]);

        self::assertSame([
            ExceptionDisabled::class => [
                'execute' => ['arguments' => [], 'return' => true, 'exception' => false],
            ],
        ], $result);
    }
}
