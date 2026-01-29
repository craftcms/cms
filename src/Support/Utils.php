<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use Closure;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionProperty;

final class Utils
{
    /** @return Collection<ReflectionProperty> */
    public static function getPublicReflectionProperties(object|string $target, ?Closure $filter = null): Collection
    {
        return collect(new ReflectionClass($target)->getProperties())
            ->filter(fn (ReflectionProperty $property) => $property->isPublic() && ! $property->isStatic() && $property->isDefault())
            ->filter($filter ?? fn () => true);
    }

    public static function getPublicProperties(object|string $target, ?Closure $filter = null): array
    {
        return self::getPublicReflectionProperties($target, $filter)
            ->mapWithKeys(function (ReflectionProperty $property) use ($target) {
                if (! $property->isInitialized($target)) {
                    // If a type of `array` is given with no value, let's assume users want
                    // it prefilled with an empty array...
                    $value = $property->getType()
                        && method_exists($property->getType(), 'getName')
                        && $property->getType()->getName() === 'array' ? [] : null;
                } else {
                    $value = $property->getValue($target);
                }

                return [$property->getName() => $value];
            })
            ->all();
    }

    public static function getPublicAttributes(object|string $target, ?Closure $filter = null): array
    {
        return self::getPublicReflectionProperties($target, $filter)
            ->map(fn (ReflectionProperty $property) => $property->getName())
            ->all();
    }
}
