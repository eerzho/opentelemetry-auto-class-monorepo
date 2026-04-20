<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Class\Laravel;

use Composer\Autoload\ClassLoader;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use OpenTelemetry\Contrib\Instrumentation\Class\AttributeScanner;
use OpenTelemetry\Contrib\Instrumentation\Class\ClassInstrumentation;
use OpenTelemetry\SDK\Sdk;
use ReflectionException;

use function extension_loaded;

final class TraceableServiceProvider extends ServiceProvider
{
    /**
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/traceable.php' => $this->app->configPath('traceable.php'),
        ], 'traceable-config');

        if (!extension_loaded('opentelemetry')) {
            return;
        }

        if (class_exists(Sdk::class) && Sdk::isInstrumentationDisabled(ClassInstrumentation::NAME)) {
            return;
        }

        /** @var Repository $config */
        $config = $this->app->make('config');

        /** @var list<string> $namespaces */
        $namespaces = $config->get('traceable.namespaces', ['App\\']);

        $classes = self::getClassesByNamespaces($namespaces);

        $map = AttributeScanner::scan($classes);
        ClassInstrumentation::register($map);
    }

    /**
     * @param list<string> $namespaces
     *
     * @return list<class-string>
     */
    private static function getClassesByNamespaces(array $namespaces): array
    {
        $loaders = ClassLoader::getRegisteredLoaders();
        $loader = reset($loaders);

        if ($loader === false) {
            return [];
        }

        /** @var list<class-string> $allClasses */
        $allClasses = array_keys($loader->getClassMap());

        return array_values(array_filter(
            $allClasses,
            static fn (string $class): bool => self::matchesNamespace($class, $namespaces),
        ));
    }

    /**
     * @param list<string> $namespaces
     */
    private static function matchesNamespace(string $class, array $namespaces): bool
    {
        foreach ($namespaces as $namespace) {
            if (str_starts_with($class, $namespace)) {
                return true;
            }
        }

        return false;
    }
}
