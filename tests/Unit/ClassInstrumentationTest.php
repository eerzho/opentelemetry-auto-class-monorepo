<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Unit;

use ArrayObject;
use DateTimeImmutable;
use DateTimeInterface;
use Eerzho\Instrumentation\Class\AttributeScanner;
use Eerzho\Instrumentation\Class\ClassInstrumentation;
use Eerzho\Instrumentation\Class\Tests\Fixtures\AddressDto;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ArgumentsDisabled;
use Eerzho\Instrumentation\Class\Tests\Fixtures\DtoService;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ExceptionDisabled;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ExcludedArguments;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ExcludedVisibilityDto;
use Eerzho\Instrumentation\Class\Tests\Fixtures\IncludedVisibilityDto;
use Eerzho\Instrumentation\Class\Tests\Fixtures\MixedArgument;
use Eerzho\Instrumentation\Class\Tests\Fixtures\MixedVisibility;
use Eerzho\Instrumentation\Class\Tests\Fixtures\MultipleArguments;
use Eerzho\Instrumentation\Class\Tests\Fixtures\NodeDto;
use Eerzho\Instrumentation\Class\Tests\Fixtures\PlainValue;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ReturnDisabled;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ReturnValue;
use Eerzho\Instrumentation\Class\Tests\Fixtures\Status;
use Eerzho\Instrumentation\Class\Tests\Fixtures\Stringable;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ThrowingMethod;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ThrowingStringable;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TracedClass;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TraceMethodWithoutTrace;
use Eerzho\Instrumentation\Class\Tests\Fixtures\UninitializedDto;
use Eerzho\Instrumentation\Class\Tests\Fixtures\UserDto;
use Eerzho\Instrumentation\Class\Tests\Fixtures\WithoutTraceClass;
use Eerzho\Instrumentation\Class\Tests\Fixtures\WrapperDto;
use OpenTelemetry\API\Instrumentation\Configurator;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\ScopeInterface;
use OpenTelemetry\SDK\Trace\ImmutableSpan;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use RuntimeException;
use stdClass;

/**
 * @internal
 */
final class ClassInstrumentationTest extends TestCase
{
    /** @var ArrayObject<int, ImmutableSpan> */
    private ArrayObject $storage;

    private ScopeInterface $scope;

    protected function setUp(): void
    {
        $this->storage = new ArrayObject();
        $exporter = new InMemoryExporter($this->storage);
        $tracerProvider = new TracerProvider(new SimpleSpanProcessor($exporter));

        $this->scope = Configurator::create()
            ->withTracerProvider($tracerProvider)
            ->activate();
    }

    protected function tearDown(): void
    {
        $this->scope->detach();
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterWithoutTraceClass(): void
    {
        $map = AttributeScanner::scan([WithoutTraceClass::class]);
        ClassInstrumentation::register($map);

        $service = new WithoutTraceClass();
        $service->doSomething();

        self::assertCount(0, $this->storage);
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterTraceMethodWithoutTrace(): void
    {
        $map = AttributeScanner::scan([TraceMethodWithoutTrace::class]);
        ClassInstrumentation::register($map);

        $service = new TraceMethodWithoutTrace();
        $service->login('test@test.com', 'secret');

        self::assertCount(0, $this->storage);
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterTracedClass(): void
    {
        $map = AttributeScanner::scan([TracedClass::class]);
        ClassInstrumentation::register($map);

        $service = new TracedClass();
        $service->greet('World');

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        self::assertSame(TracedClass::class . '::greet', $span->getName());
        self::assertSame(SpanKind::KIND_INTERNAL, $span->getKind());
        self::assertSame(TracedClass::class . '::greet', $attributes->get('code.function.name'));
        self::assertNotNull($attributes->get('code.file.path'));
        self::assertNotNull($attributes->get('code.line.number'));
        self::assertSame('World', $attributes->get('code.argument.name'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterCapturesReturnValue(): void
    {
        $map = AttributeScanner::scan([ReturnValue::class]);
        ClassInstrumentation::register($map);

        (new ReturnValue())->compute(2, 3);

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        self::assertSame(ReturnValue::class . '::compute', $span->getName());
        self::assertSame(2, $attributes->get('code.argument.a'));
        self::assertSame(3, $attributes->get('code.argument.b'));
        self::assertSame(5, $attributes->get('code.return'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterReturnDisabled(): void
    {
        $map = AttributeScanner::scan([ReturnDisabled::class]);
        ClassInstrumentation::register($map);

        $service = new ReturnDisabled();
        $service->compute();

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        self::assertSame(ReturnDisabled::class . '::compute', $span->getName());
        self::assertFalse($attributes->has('code.return'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterArgumentsDisabled(): void
    {
        $map = AttributeScanner::scan([ArgumentsDisabled::class]);
        ClassInstrumentation::register($map);

        $service = new ArgumentsDisabled();
        $service->process('secret');

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        self::assertSame(ArgumentsDisabled::class . '::process', $span->getName());
        self::assertFalse($attributes->has('code.argument.value'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterExcludedArguments(): void
    {
        $map = AttributeScanner::scan([ExcludedArguments::class]);
        ClassInstrumentation::register($map);

        (new ExcludedArguments())->process('a', 'b', 'c');

        self::assertCount(1, $this->storage);
        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        self::assertSame('a', $attributes->get('code.argument.first'));
        self::assertSame('c', $attributes->get('code.argument.third'));
        self::assertFalse($attributes->has('code.argument.second'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterMixedVisibility(): void
    {
        $map = AttributeScanner::scan([MixedVisibility::class]);
        ClassInstrumentation::register($map);

        // publicMethod() calls the protected and private ones, so all three are hooked
        (new MixedVisibility())->publicMethod();

        self::assertCount(3, $this->storage);

        $names = array_map(
            static fn (ImmutableSpan $span): string => $span->getName(),
            $this->storage->getArrayCopy(),
        );
        self::assertContains(MixedVisibility::class . '::publicMethod', $names);
        self::assertContains(MixedVisibility::class . '::protectedMethod', $names);
        self::assertContains(MixedVisibility::class . '::privateMethod', $names);
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterMultipleArguments(): void
    {
        $map = AttributeScanner::scan([MultipleArguments::class]);
        ClassInstrumentation::register($map);

        (new MultipleArguments())->execute('hello', 42, true, 3.14);

        self::assertCount(1, $this->storage);
        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        self::assertSame('hello', $attributes->get('code.argument.first'));
        self::assertSame(42, $attributes->get('code.argument.second'));
        self::assertTrue($attributes->get('code.argument.third'));
        self::assertSame(3.14, $attributes->get('code.argument.fourth'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterNullArgument(): void
    {
        $map = AttributeScanner::scan([MixedArgument::class]);
        ClassInstrumentation::register($map);

        (new MixedArgument())->process(null);

        self::assertCount(1, $this->storage);
        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);
        self::assertSame('null', $span->getAttributes()->get('code.argument.value'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterBackedEnumArgument(): void
    {
        $map = AttributeScanner::scan([MixedArgument::class]);
        ClassInstrumentation::register($map);

        (new MixedArgument())->process(Status::Active);

        self::assertCount(1, $this->storage);
        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);
        self::assertSame('active', $span->getAttributes()->get('code.argument.value'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterDateTimeArgument(): void
    {
        $map = AttributeScanner::scan([MixedArgument::class]);
        ClassInstrumentation::register($map);

        $at = new DateTimeImmutable('2026-07-16T12:34:56.789+00:00');
        (new MixedArgument())->process($at);

        self::assertCount(1, $this->storage);
        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);
        self::assertSame($at->format(DateTimeInterface::RFC3339_EXTENDED), $span->getAttributes()->get('code.argument.value'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterObjectWithToStringArgument(): void
    {
        $map = AttributeScanner::scan([MixedArgument::class]);
        ClassInstrumentation::register($map);

        (new MixedArgument())->process(new Stringable('hello'));

        self::assertCount(1, $this->storage);
        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);
        self::assertSame('hello', $span->getAttributes()->get('code.argument.value'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterObjectWithoutToStringArgument(): void
    {
        $map = AttributeScanner::scan([MixedArgument::class]);
        ClassInstrumentation::register($map);

        (new MixedArgument())->process(new stdClass());

        self::assertCount(1, $this->storage);
        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);
        self::assertSame('stdClass', $span->getAttributes()->get('code.argument.value'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterArrayArgument(): void
    {
        $map = AttributeScanner::scan([MixedArgument::class]);
        ClassInstrumentation::register($map);

        (new MixedArgument())->process(['a' => 1, 'b' => 2]);

        self::assertCount(1, $this->storage);
        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);
        $attributes = $span->getAttributes();
        self::assertSame(1, $attributes->get('code.argument.value.a'));
        self::assertFalse($attributes->has('code.argument.value.b')); // only the first element is sampled
        self::assertSame(2, $attributes->get('code.argument.value.array_count'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterArrayOfObjectsArgument(): void
    {
        $map = AttributeScanner::scan([MixedArgument::class]);
        ClassInstrumentation::register($map);

        (new MixedArgument())->process([
            new AddressDto('Almaty', '050000'),
            new AddressDto('Astana', '010000'),
        ]);

        self::assertCount(1, $this->storage);
        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);
        $attributes = $span->getAttributes();
        self::assertSame('Almaty', $attributes->get('code.argument.value.0.city'));
        self::assertSame('050000', $attributes->get('code.argument.value.0.zip'));
        self::assertFalse($attributes->has('code.argument.value.1.city')); // only the first element is sampled
        self::assertSame(2, $attributes->get('code.argument.value.array_count'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterEmptyArrayArgument(): void
    {
        $map = AttributeScanner::scan([MixedArgument::class]);
        ClassInstrumentation::register($map);

        (new MixedArgument())->process([]);

        self::assertCount(1, $this->storage);
        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);
        self::assertSame(0, $span->getAttributes()->get('code.argument.value.array_count'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterResourceArgument(): void
    {
        $map = AttributeScanner::scan([MixedArgument::class]);
        ClassInstrumentation::register($map);

        $resource = fopen('php://memory', 'r');
        self::assertIsResource($resource);

        (new MixedArgument())->process($resource);
        fclose($resource);

        self::assertCount(1, $this->storage);
        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);
        self::assertSame('resource', $span->getAttributes()->get('code.argument.value'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterExceptionSetsErrorStatus(): void
    {
        $map = AttributeScanner::scan([ThrowingMethod::class]);
        ClassInstrumentation::register($map);

        $service = new ThrowingMethod();

        try {
            $service->execute();
        } catch (RuntimeException) {
        }

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $status = $span->getStatus();
        $events = $span->getEvents();

        self::assertSame(StatusCode::STATUS_ERROR, $status->getCode());
        self::assertSame('something went wrong', $status->getDescription());

        self::assertCount(1, $events);
        self::assertSame('exception', $events[0]->getName());
        self::assertSame('RuntimeException', $events[0]->getAttributes()->get('exception.type'));
        self::assertSame('something went wrong', $events[0]->getAttributes()->get('exception.message'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterExceptionDisabled(): void
    {
        $map = AttributeScanner::scan([ExceptionDisabled::class]);
        ClassInstrumentation::register($map);

        $service = new ExceptionDisabled();

        try {
            $service->execute();
        } catch (RuntimeException) {
        }

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $status = $span->getStatus();

        // status still flips to ERROR, but the exception details are not recorded
        self::assertSame(StatusCode::STATUS_ERROR, $status->getCode());
        self::assertSame('', $status->getDescription());
        self::assertCount(0, $span->getEvents());
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterExpandsTraceProperties(): void
    {
        $map = AttributeScanner::scan([DtoService::class]);
        ClassInstrumentation::register($map);

        (new DtoService())->handle(new UserDto(7, 'Ann', new AddressDto('Almaty', '050000')));

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        self::assertSame(7, $attributes->get('code.argument.dto.id'));
        self::assertSame('Ann', $attributes->get('code.argument.dto.name'));
        self::assertSame('Almaty', $attributes->get('code.argument.dto.address.city'));
        self::assertSame('050000', $attributes->get('code.argument.dto.address.zip'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterFiltersTraceProperties(): void
    {
        $map = AttributeScanner::scan([DtoService::class]);
        ClassInstrumentation::register($map);

        (new DtoService())->handle(new ExcludedVisibilityDto());

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        // one excluded per visibility -> gone
        self::assertFalse($attributes->has('code.argument.dto.publicOne'));
        self::assertFalse($attributes->has('code.argument.dto.protectedOne'));
        self::assertFalse($attributes->has('code.argument.dto.privateOne'));
        // the rest stay, regardless of visibility
        self::assertSame('public-two', $attributes->get('code.argument.dto.publicTwo'));
        self::assertSame('protected-two', $attributes->get('code.argument.dto.protectedTwo'));
        self::assertSame('private-two', $attributes->get('code.argument.dto.privateTwo'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterHandlesCyclicTraceProperties(): void
    {
        $map = AttributeScanner::scan([DtoService::class]);
        ClassInstrumentation::register($map);

        $node = new NodeDto(1);
        $node->next = $node;
        (new DtoService())->handle($node);

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        self::assertSame(1, $attributes->get('code.argument.dto.id'));
        // cycle is broken: the revisited object degrades to its class name
        self::assertSame(NodeDto::class, $attributes->get('code.argument.dto.next'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterFallsBackForPlainNestedObject(): void
    {
        $map = AttributeScanner::scan([DtoService::class]);
        ClassInstrumentation::register($map);

        (new DtoService())->handle(new WrapperDto(new PlainValue('x')));

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        // no #[TraceProperties] on PlainValue -> falls back to class name
        self::assertSame(PlainValue::class, $attributes->get('code.argument.dto.inner'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterSkipsUninitializedProperty(): void
    {
        $map = AttributeScanner::scan([DtoService::class]);
        ClassInstrumentation::register($map);

        (new DtoService())->handle(new UninitializedDto('Bob'));

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        // initialized property is captured, uninitialized one gets the "uninitialized" marker (no crash)
        self::assertSame('Bob', $attributes->get('code.argument.dto.name'));
        self::assertSame('uninitialized', $attributes->get('code.argument.dto.ready'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterDegradesWhenToStringThrows(): void
    {
        $map = AttributeScanner::scan([DtoService::class]);
        ClassInstrumentation::register($map);

        (new DtoService())->handle(new ThrowingStringable());

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        // __toString() throws -> degrades to class name, never propagates to the traced method
        self::assertSame(ThrowingStringable::class, $attributes->get('code.argument.dto'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterIncludesOnlyAllowlistedProperties(): void
    {
        $map = AttributeScanner::scan([DtoService::class]);
        ClassInstrumentation::register($map);

        (new DtoService())->handle(new IncludedVisibilityDto());

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        // only the allowlisted one per visibility
        self::assertSame('public-one', $attributes->get('code.argument.dto.publicOne'));
        self::assertSame('protected-one', $attributes->get('code.argument.dto.protectedOne'));
        self::assertSame('private-one', $attributes->get('code.argument.dto.privateOne'));
        // the rest are dropped, regardless of visibility
        self::assertFalse($attributes->has('code.argument.dto.publicTwo'));
        self::assertFalse($attributes->has('code.argument.dto.protectedTwo'));
        self::assertFalse($attributes->has('code.argument.dto.privateTwo'));
    }
}
