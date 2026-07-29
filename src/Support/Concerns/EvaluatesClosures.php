<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Concerns;

use Closure;
use ReflectionFunction;
use ReflectionNamedType;

/**
 * Resolves Filament-style callback properties. Closure parameters are injected
 * by name first, then by type, then from the container, so callers can type-hint
 * whatever they need. View components resolve values at render time, while
 * persisted builders can resolve them eagerly:
 *
 *     Field::make()->label(fn (Field $field) => ...)
 *     Field::make()->required(fn (GeneralConfig $config) => ...)
 */
trait EvaluatesClosures
{
    /**
     * @template T
     *
     * @param  T|Closure  $value
     * @param  array<string, mixed>  $namedInjections  Parameter-name => value
     * @param  array<class-string, mixed>  $typedInjections  Type => value
     * @return T
     */
    public function evaluate(
        mixed $value,
        array $namedInjections = [],
        array $typedInjections = [],
    ): mixed {
        if (! $value instanceof Closure) {
            return $value;
        }

        $namedInjections = [
            ...$this->defaultEvaluationNamedInjections(),
            ...$namedInjections,
        ];
        $typedInjections = [
            ...$this->defaultEvaluationTypedInjections(),
            ...$typedInjections,
        ];

        $arguments = [];

        foreach (new ReflectionFunction($value)->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $namedInjections)) {
                $arguments[$name] = $namedInjections[$name];

                continue;
            }

            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $typeName = $type->getName();

                foreach ($typedInjections as $class => $injection) {
                    if (is_a($class, $typeName, true)) {
                        $arguments[$name] = $injection;

                        continue 2;
                    }
                }

                $arguments[$name] = app($typeName);

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[$name] = $parameter->getDefaultValue();
            }
        }

        return $value(...$arguments);
    }

    /** @return array<string, mixed> */
    protected function defaultEvaluationNamedInjections(): array
    {
        return [
            'component' => $this,
        ];
    }

    /** @return array<class-string, mixed> */
    protected function defaultEvaluationTypedInjections(): array
    {
        return [
            static::class => $this,
        ];
    }
}
