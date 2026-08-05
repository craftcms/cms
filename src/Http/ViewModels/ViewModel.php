<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Support\Utils;
use Illuminate\Contracts\Support\Arrayable;
use ReflectionClass;
use ReflectionMethod;

/**
 * Base class for Inertia page payloads.
 *
 * Public properties are returned as-is. Public zero-argument instance methods on
 * concrete view models are called and returned under their method name, which
 * keeps constructors focused on required dependencies and lets view models expose
 * derived payload data without storing duplicate properties.
 */
/** @implements Arrayable<string, mixed> */
abstract class ViewModel implements Arrayable
{
    public function toArray(): array
    {
        return [
            ...Utils::getPublicProperties($this),
            ...$this->publicMethodValues(),
        ];
    }

    /** @return array<string, mixed> */
    private function publicMethodValues(): array
    {
        return collect(new ReflectionClass($this)->getMethods(ReflectionMethod::IS_PUBLIC))
            ->filter(fn (ReflectionMethod $method): bool => $method->getDeclaringClass()->getName() !== self::class
                && ! $method->isConstructor()
                && ! $method->isStatic())
            ->mapWithKeys(fn (ReflectionMethod $method): array => [
                $method->getName() => $method->invoke($this),
            ])
            ->all();
    }
}
