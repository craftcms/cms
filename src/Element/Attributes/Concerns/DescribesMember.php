<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Attributes\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Shared behavior for attributes that describe a class member, such as an element property or query param.
 *
 * @internal
 */
trait DescribesMember
{
    private ReflectionProperty|ReflectionMethod $declaration;

    /**
     * Returns the description for an element type, replacing `{element}` with its lowercase name and `{elements}` with
     * its plural lowercase name.
     *
     * @param  class-string<ElementInterface>  $elementType
     */
    public function descriptionFor(string $elementType): string
    {
        return str_replace(
            ['{element}', '{elements}'],
            [$elementType::lowerDisplayName(), $elementType::pluralLowerDisplayName()],
            $this->description,
        );
    }

    /**
     * Returns whether the member applies to an element type, which it does unless it requires a capability the element
     * type lacks.
     *
     * @param  class-string<ElementInterface>  $elementType
     */
    public function appliesTo(string $elementType): bool
    {
        return $this->requires?->supportedBy($elementType) ?? true;
    }

    /**
     * Returns another attribute placed alongside this one, such as an API-specific attribute that controls how the API
     * exposes the member.
     *
     * @template T of object
     *
     * @param  class-string<T>  $attribute
     * @return T|null
     */
    public function companion(string $attribute): ?object
    {
        return ($this->declaration->getAttributes($attribute)[0] ?? null)?->newInstance();
    }

    /**
     * Instantiates the attribute that a property or method carries.
     */
    private static function fromDeclaration(ReflectionProperty|ReflectionMethod $declaration): static
    {
        $instance = $declaration->getAttributes(static::class)[0]->newInstance();
        $instance->declaration = $declaration;

        return $instance;
    }

    /**
     * Returns the method that carries the attribute: the method itself, or the method it overrides.
     */
    private static function declarationOf(ReflectionMethod $method): ?ReflectionMethod
    {
        if ($method->getAttributes(static::class)) {
            return $method;
        }

        $parent = $method->getDeclaringClass()->getParentClass();

        return $parent && $parent->hasMethod($method->getName())
            ? self::declarationOf($parent->getMethod($method->getName()))
            : null;
    }
}
