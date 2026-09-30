<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use Attribute;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Container\ContextualAttribute;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

/**
 * Injects the element matched for the current site request.
 * Nullable parameters with a default receive null when no compatible element matches.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class CurrentElement implements ContextualAttribute
{
    public static function resolve(self $attribute, Container $container, ReflectionParameter $parameter): ?ElementInterface
    {
        $element = MatchedElement::get();

        if ($element && self::matchesType($parameter->getType(), $element, $parameter)) {
            return $element;
        }

        abort_unless($parameter->isDefaultValueAvailable() && $parameter->allowsNull(), 404);

        return null;
    }

    private static function matchesType(?ReflectionType $type, ElementInterface $element, ReflectionParameter $parameter): bool
    {
        if ($type === null) {
            return true;
        }

        if ($type instanceof ReflectionUnionType) {
            return array_any($type->getTypes(), fn (ReflectionType $member): bool => self::matchesType($member, $element, $parameter));
        }

        if ($type instanceof ReflectionIntersectionType) {
            return array_all($type->getTypes(), fn (ReflectionType $member): bool => self::matchesType($member, $element, $parameter));
        }

        if (! $type instanceof ReflectionNamedType) {
            return false;
        }

        if ($type->isBuiltin()) {
            return match ($type->getName()) {
                'mixed', 'object', 'iterable' => true,
                'callable' => is_callable($element),
                default => false,
            };
        }

        $class = $type->getName();
        $declaringClass = $parameter->getDeclaringClass();

        if ($class === 'self' && $declaringClass) {
            $class = $declaringClass->getName();
        } elseif ($class === 'parent' && ($parent = $declaringClass?->getParentClass())) {
            $class = $parent->getName();
        }

        return $element instanceof $class;
    }
}
