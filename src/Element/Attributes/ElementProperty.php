<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Attributes;

use Attribute;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Enums\ElementPropertyType;
use LogicException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Describes an element property so that external APIs can read or write it.
 *
 * The attribute can be placed on a public property, on its setter, or on a getter for a computed value. Overriding
 * methods inherit the attribute from the method they override.
 *
 * Descriptions shared by several element types can use an `{element}` placeholder for the element type's lowercase
 * name.
 *
 * @since 6.0.0
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class ElementProperty
{
    /** @var array<class-string, array<string, self>> */
    private static array $properties = [];

    /**
     * @param  bool  $nullable  Whether the property accepts `null` when it’s written.
     * @param  bool  $readable  Whether the property is part of the element’s documented output, which public APIs may expose.
     * @param  bool  $writable  Whether APIs may set the property.
     * @param  bool  $nonNull  Whether reading the property always returns a value.
     */
    public function __construct(
        public readonly ElementPropertyType $type,
        public readonly string $description,
        public readonly bool $nullable = false,
        public readonly bool $readable = false,
        public readonly bool $writable = true,
        public readonly bool $nonNull = false,
    ) {}

    /**
     * Returns the description for an element type, replacing `{element}` with its lowercase name.
     *
     * @param  class-string<ElementInterface>  $elementType
     */
    public function descriptionFor(string $elementType): string
    {
        return str_replace('{element}', $elementType::lowerDisplayName(), $this->description);
    }

    /**
     * Returns the properties an element type declares, indexed by property name.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @return array<string, self>
     */
    public static function all(string $elementType): array
    {
        if (isset(self::$properties[$elementType])) {
            return self::$properties[$elementType];
        }

        $class = new ReflectionClass($elementType);
        $properties = [];

        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (! $property->isStatic() && ($attribute = $property->getAttributes(self::class)[0] ?? null)) {
                $properties[$property->getName()] = $attribute->newInstance();
            }
        }

        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();

            if ($method->isStatic() || ! preg_match('/^[gs]et[A-Z]/', $name) || ! ($property = self::declaredOn($method))) {
                continue;
            }

            $propertyName = lcfirst(substr($name, 3));

            if (isset($properties[$propertyName])) {
                throw new LogicException("[$elementType] declares the element property [$propertyName] more than once.");
            }

            $properties[$propertyName] = $property;
        }

        return self::$properties[$elementType] = $properties;
    }

    /**
     * Returns the properties an element type declares as readable, indexed by property name.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @return array<string, self>
     */
    public static function readable(string $elementType): array
    {
        return array_filter(self::all($elementType), fn (self $property): bool => $property->readable);
    }

    /**
     * Returns the given properties of an element type, ensuring that each is declared with the given access.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  list<string>  $names
     * @param  'readable'|'writable'  $access
     * @return array<string, self>
     */
    public static function only(string $elementType, array $names, string $access): array
    {
        $properties = self::all($elementType);
        $only = [];

        foreach ($names as $name) {
            $property = $properties[$name] ?? null;

            if (! $property?->$access) {
                throw new LogicException("[$elementType] does not declare a $access element property [$name].");
            }

            $only[$name] = $property;
        }

        return $only;
    }

    private static function declaredOn(ReflectionMethod $method): ?self
    {
        $attribute = $method->getAttributes(self::class)[0] ?? null;

        if ($attribute) {
            return $attribute->newInstance();
        }

        $parent = $method->getDeclaringClass()->getParentClass();

        return $parent && $parent->hasMethod($method->getName())
            ? self::declaredOn($parent->getMethod($method->getName()))
            : null;
    }
}
