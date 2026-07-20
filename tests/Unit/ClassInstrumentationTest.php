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
use Eerzho\Instrumentation\Class\Tests\Fixtures\FilteredDto;
use Eerzho\Instrumentation\Class\Tests\Fixtures\IncludedPropertiesDto;
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
        self::assertSame('World', $attributes->get('name'));
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
        self::assertSame(2, $attributes->get('a'));
        self::assertSame(3, $attributes->get('b'));
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
        self::assertFalse($attributes->has('value'));
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
        self::assertSame('a', $attributes->get('first'));
        self::assertSame('c', $attributes->get('third'));
        self::assertFalse($attributes->has('second'));
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
        self::assertSame('hello', $attributes->get('first'));
        self::assertSame(42, $attributes->get('second'));
        self::assertTrue($attributes->get('third'));
        self::assertSame(3.14, $attributes->get('fourth'));
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
        self::assertSame('null', $span->getAttributes()->get('value'));
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
        self::assertSame('active', $span->getAttributes()->get('value'));
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
        self::assertSame($at->format(DateTimeInterface::RFC3339_EXTENDED), $span->getAttributes()->get('value'));
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
        self::assertSame('hello', $span->getAttributes()->get('value'));
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
        self::assertSame('stdClass', $span->getAttributes()->get('value'));
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
        self::assertSame('{"a":1,"b":2}', $span->getAttributes()->get('value'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterArrayArgumentWithJsonException(): void
    {
        $map = AttributeScanner::scan([MixedArgument::class]);
        ClassInstrumentation::register($map);

        (new MixedArgument())->process([NAN]);

        self::assertCount(1, $this->storage);
        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);
        self::assertSame('array', $span->getAttributes()->get('value'));
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
        self::assertSame('resource', $span->getAttributes()->get('value'));
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
        self::assertSame(7, $attributes->get('dto.id'));
        self::assertSame('Ann', $attributes->get('dto.name'));
        self::assertSame('Almaty', $attributes->get('dto.address.city'));
        self::assertSame('050000', $attributes->get('dto.address.zip'));
        // private properties are not expanded
        self::assertFalse($attributes->has('dto.passwordHash'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterFiltersTraceProperties(): void
    {
        $map = AttributeScanner::scan([DtoService::class]);
        ClassInstrumentation::register($map);

        (new DtoService())->handle(new FilteredDto('a@b.c', 'secret-token'));

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        self::assertSame('a@b.c', $attributes->get('dto.email'));
        self::assertFalse($attributes->has('dto.token'));
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
        self::assertSame(1, $attributes->get('dto.id'));
        // cycle is broken: the revisited object degrades to its class name
        self::assertSame(NodeDto::class, $attributes->get('dto.next'));
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
        self::assertSame(PlainValue::class, $attributes->get('dto.inner'));
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
        self::assertSame('Bob', $attributes->get('dto.name'));
        self::assertSame('uninitialized', $attributes->get('dto.ready'));
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
        self::assertSame(ThrowingStringable::class, $attributes->get('dto'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterIncludesOnlyAllowlistedProperties(): void
    {
        $map = AttributeScanner::scan([DtoService::class]);
        ClassInstrumentation::register($map);

        (new DtoService())->handle(new IncludedPropertiesDto(7, 'Ann'));

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        self::assertSame(7, $attributes->get('dto.id'));
        // "name" is not in the include allowlist
        self::assertFalse($attributes->has('dto.name'));
    }
}
