<?php

declare(strict_types=1);

namespace Eerzho\Instrumentation\Class\Tests\Unit;

use ArrayObject;
use DateTimeImmutable;
use DateTimeInterface;
use Eerzho\Instrumentation\Class\AttributeScanner;
use Eerzho\Instrumentation\Class\ClassInstrumentation;
use Eerzho\Instrumentation\Class\Tests\Fixtures\AddressDto;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ArrayArgument;
use Eerzho\Instrumentation\Class\Tests\Fixtures\BackedEnumArgument;
use Eerzho\Instrumentation\Class\Tests\Fixtures\DateTimeArgument;
use Eerzho\Instrumentation\Class\Tests\Fixtures\DtoService;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ExcludedArguments;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ExcludedMethods;
use Eerzho\Instrumentation\Class\Tests\Fixtures\FilteredDto;
use Eerzho\Instrumentation\Class\Tests\Fixtures\IncludedPropertiesDto;
use Eerzho\Instrumentation\Class\Tests\Fixtures\MixedArgument;
use Eerzho\Instrumentation\Class\Tests\Fixtures\MixedVisibility;
use Eerzho\Instrumentation\Class\Tests\Fixtures\MultipleArguments;
use Eerzho\Instrumentation\Class\Tests\Fixtures\NodeDto;
use Eerzho\Instrumentation\Class\Tests\Fixtures\NullableArgument;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ObjectArgument;
use Eerzho\Instrumentation\Class\Tests\Fixtures\PlainValue;
use Eerzho\Instrumentation\Class\Tests\Fixtures\Status;
use Eerzho\Instrumentation\Class\Tests\Fixtures\Stringable;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ThrowingMethod;
use Eerzho\Instrumentation\Class\Tests\Fixtures\ThrowingStringable;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TraceArgumentsWithoutTrace;
use Eerzho\Instrumentation\Class\Tests\Fixtures\TracedClass;
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
    public function testRegisterTraceArgumentsWithoutTrace(): void
    {
        $map = AttributeScanner::scan([TraceArgumentsWithoutTrace::class]);
        ClassInstrumentation::register($map);

        $service = new TraceArgumentsWithoutTrace();
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
    public function testRegisterExcludedMethods(): void
    {
        $map = AttributeScanner::scan([ExcludedMethods::class]);
        ClassInstrumentation::register($map);

        $service = new ExcludedMethods();
        $service->visible();
        $service->secret();

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        self::assertSame(ExcludedMethods::class . '::visible', $span->getName());
        self::assertSame(SpanKind::KIND_INTERNAL, $span->getKind());
        self::assertSame(ExcludedMethods::class . '::visible', $attributes->get('code.function.name'));
        self::assertNotNull($attributes->get('code.file.path'));
        self::assertNotNull($attributes->get('code.line.number'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterExcludedArguments(): void
    {
        $map = AttributeScanner::scan([ExcludedArguments::class]);
        ClassInstrumentation::register($map);

        $service = new ExcludedArguments();
        $service->process('a', 'b', 'c');

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        self::assertSame(ExcludedArguments::class . '::process', $span->getName());
        self::assertSame(SpanKind::KIND_INTERNAL, $span->getKind());
        self::assertSame(ExcludedArguments::class . '::process', $attributes->get('code.function.name'));
        self::assertNotNull($attributes->get('code.file.path'));
        self::assertNotNull($attributes->get('code.line.number'));
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

        $service = new MixedVisibility();
        $service->publicMethod();

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();

        self::assertSame(MixedVisibility::class . '::publicMethod', $span->getName());
        self::assertSame(SpanKind::KIND_INTERNAL, $span->getKind());
        self::assertSame(MixedVisibility::class . '::publicMethod', $attributes->get('code.function.name'));
        self::assertNotNull($attributes->get('code.file.path'));
        self::assertNotNull($attributes->get('code.line.number'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterMultipleArguments(): void
    {
        $map = AttributeScanner::scan([MultipleArguments::class]);
        ClassInstrumentation::register($map);

        $service = new MultipleArguments();
        $service->execute('hello', 42, true, 3.14);

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        self::assertSame(MultipleArguments::class . '::execute', $span->getName());
        self::assertSame(SpanKind::KIND_INTERNAL, $span->getKind());
        self::assertSame(MultipleArguments::class . '::execute', $attributes->get('code.function.name'));
        self::assertNotNull($attributes->get('code.file.path'));
        self::assertNotNull($attributes->get('code.line.number'));
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
        $map = AttributeScanner::scan([NullableArgument::class]);
        ClassInstrumentation::register($map);

        $service = new NullableArgument();
        $service->process(null);

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        self::assertSame(NullableArgument::class . '::process', $span->getName());
        self::assertSame(SpanKind::KIND_INTERNAL, $span->getKind());
        self::assertSame(NullableArgument::class . '::process', $attributes->get('code.function.name'));
        self::assertNotNull($attributes->get('code.file.path'));
        self::assertNotNull($attributes->get('code.line.number'));
        self::assertSame('null', $attributes->get('value'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterBackedEnumArgument(): void
    {
        $map = AttributeScanner::scan([BackedEnumArgument::class]);
        ClassInstrumentation::register($map);

        $service = new BackedEnumArgument();
        $service->setStatus(Status::Active);

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        self::assertSame(BackedEnumArgument::class . '::setStatus', $span->getName());
        self::assertSame(SpanKind::KIND_INTERNAL, $span->getKind());
        self::assertSame(BackedEnumArgument::class . '::setStatus', $attributes->get('code.function.name'));
        self::assertNotNull($attributes->get('code.file.path'));
        self::assertNotNull($attributes->get('code.line.number'));
        self::assertSame('active', $attributes->get('status'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterDateTimeArgument(): void
    {
        $map = AttributeScanner::scan([DateTimeArgument::class]);
        ClassInstrumentation::register($map);

        $service = new DateTimeArgument();
        $at = new DateTimeImmutable('2026-07-16T12:34:56.789+00:00');
        $service->process($at);

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        self::assertSame($at->format(DateTimeInterface::RFC3339_EXTENDED), $attributes->get('at'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterObjectWithToStringArgument(): void
    {
        $map = AttributeScanner::scan([ObjectArgument::class]);
        ClassInstrumentation::register($map);

        $service = new ObjectArgument();
        $service->process(new Stringable('hello'));

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        self::assertSame(ObjectArgument::class . '::process', $span->getName());
        self::assertSame(SpanKind::KIND_INTERNAL, $span->getKind());
        self::assertSame(ObjectArgument::class . '::process', $attributes->get('code.function.name'));
        self::assertNotNull($attributes->get('code.file.path'));
        self::assertNotNull($attributes->get('code.line.number'));
        self::assertSame('hello', $attributes->get('item'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterObjectWithoutToStringArgument(): void
    {
        $map = AttributeScanner::scan([ObjectArgument::class]);
        ClassInstrumentation::register($map);

        $service = new ObjectArgument();
        $service->process(new stdClass());

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        self::assertSame(ObjectArgument::class . '::process', $span->getName());
        self::assertSame(SpanKind::KIND_INTERNAL, $span->getKind());
        self::assertSame(ObjectArgument::class . '::process', $attributes->get('code.function.name'));
        self::assertNotNull($attributes->get('code.file.path'));
        self::assertNotNull($attributes->get('code.line.number'));
        self::assertSame('stdClass', $attributes->get('item'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterArrayArgument(): void
    {
        $map = AttributeScanner::scan([ArrayArgument::class]);
        ClassInstrumentation::register($map);

        $service = new ArrayArgument();
        $service->process(['a' => 1, 'b' => 2]);

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        self::assertSame(ArrayArgument::class . '::process', $span->getName());
        self::assertSame(SpanKind::KIND_INTERNAL, $span->getKind());
        self::assertSame(ArrayArgument::class . '::process', $attributes->get('code.function.name'));
        self::assertNotNull($attributes->get('code.file.path'));
        self::assertNotNull($attributes->get('code.line.number'));
        self::assertSame('{"a":1,"b":2}', $attributes->get('items'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterArrayArgumentWithJsonException(): void
    {
        $map = AttributeScanner::scan([ArrayArgument::class]);
        ClassInstrumentation::register($map);

        $service = new ArrayArgument();
        $service->process([NAN]);

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        self::assertSame(ArrayArgument::class . '::process', $span->getName());
        self::assertSame(SpanKind::KIND_INTERNAL, $span->getKind());
        self::assertSame(ArrayArgument::class . '::process', $attributes->get('code.function.name'));
        self::assertNotNull($attributes->get('code.file.path'));
        self::assertNotNull($attributes->get('code.line.number'));
        self::assertSame('array', $attributes->get('items'));
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

        $service = new MixedArgument();
        $service->process($resource);
        fclose($resource);

        self::assertCount(1, $this->storage);

        $span = $this->storage[0];
        self::assertInstanceOf(ImmutableSpan::class, $span);

        $attributes = $span->getAttributes();
        self::assertSame(MixedArgument::class . '::process', $span->getName());
        self::assertSame(SpanKind::KIND_INTERNAL, $span->getKind());
        self::assertSame(MixedArgument::class . '::process', $attributes->get('code.function.name'));
        self::assertNotNull($attributes->get('code.file.path'));
        self::assertNotNull($attributes->get('code.line.number'));
        self::assertSame('resource', $attributes->get('value'));
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

        $attributes = $span->getAttributes();
        $status = $span->getStatus();
        $events = $span->getEvents();

        self::assertSame(ThrowingMethod::class . '::execute', $span->getName());
        self::assertSame(SpanKind::KIND_INTERNAL, $span->getKind());
        self::assertSame(ThrowingMethod::class . '::execute', $attributes->get('code.function.name'));
        self::assertNotNull($attributes->get('code.file.path'));
        self::assertNotNull($attributes->get('code.line.number'));

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
