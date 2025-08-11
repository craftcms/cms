<?php

namespace CraftCms\Cms\Support;

use ReflectionObject;
use ReflectionProperty;

final class Utils
{
    public static function getPublicProperties($target, $filter = null)
    {
        return collect(new ReflectionObject($target)->getProperties())
            ->filter(function (ReflectionProperty $property) {
                return $property->isPublic() && ! $property->isStatic() && $property->isDefault();
            })
            ->filter($filter ?? fn () => true)
            ->mapWithKeys(function (ReflectionProperty $property) use ($target) {
                if (! $property->isInitialized($target)) {
                    // If a type of `array` is given with no value, let's assume users want
                    // it prefilled with an empty array...
                    $value = $property->getType() && method_exists($property->getType(), 'getName') && $property->getType()->getName() === 'array'
                        ? []
                        : null;
                } else {
                    $value = $property->getValue($target);
                }

                return [$property->getName() => $value];
            })
            ->all();
    }
}
