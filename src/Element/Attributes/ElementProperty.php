<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Attributes;

use Attribute;
use CraftCms\Cms\Element\Attributes\Concerns\DescribesMember;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Enums\ElementCapability;
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
 * Descriptions shared by several element types can use `{element}` and `{elements}` placeholders for the element
 * type's lowercase singular and plural names.
 *
 * @since 6.0.0
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class ElementProperty
{
    use DescribesMember;

    /** @var array<class-string, array<string, self>> */
    private static array $properties = [];

    /**
     * @param  bool  $nullable  Whether the property accepts `null` when it’s written.
     * @param  bool  $readable  Whether the property is part of the element’s documented output, which public APIs may expose.
     * @param  bool  $writable  Whether APIs may set the property.
     * @param  bool  $nonNull  Whether reading the property always returns a value.
     * @param  ElementCapability|null  $requires  The capability an element type needs for the property to apply to it.
     */
    public function __construct(
        public readonly ElementPropertyType $type,
        public readonly string $description,
        public readonly bool $nullable = false,
        public readonly bool $readable = false,
        public readonly bool $writable = true,
        public readonly bool $nonNull = false,
        public readonly ?ElementCapability $requires = null,
    ) {}

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
            if (! $property->isStatic() && $property->getAttributes(self::class)) {
                $properties[$property->getName()] = self::fromDeclaration($property);
            }
        }

        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();

            if ($method->isStatic() || ! preg_match('/^[gs]et[A-Z]/', $name) || ! ($declaration = self::declarationOf($method))) {
                continue;
            }

            $propertyName = lcfirst(substr($name, 3));

            if (isset($properties[$propertyName])) {
                throw new LogicException("[$elementType] declares the element property [$propertyName] more than once.");
            }

            $properties[$propertyName] = self::fromDeclaration($declaration);
        }

        return self::$properties[$elementType] = $properties;
    }

    /**
     * Returns the readable properties that apply to an element type, indexed by property name.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  list<string>|null  $names  Property names to return, in this order, whether or not they apply to the element type. Every name must be declared as readable.
     * @return array<string, self>
     */
    public static function readable(string $elementType, ?array $names = null): array
    {
        return self::withAccess($elementType, 'readable', $names);
    }

    /**
     * Returns the writable properties that apply to an element type, indexed by property name.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  list<string>|null  $names  Property names to return, in this order, whether or not they apply to the element type. Every name must be declared as writable.
     * @return array<string, self>
     */
    public static function writable(string $elementType, ?array $names = null): array
    {
        return self::withAccess($elementType, 'writable', $names);
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  'readable'|'writable'  $access
     * @param  list<string>|null  $names
     * @return array<string, self>
     */
    private static function withAccess(string $elementType, string $access, ?array $names): array
    {
        $properties = array_filter(self::all($elementType), fn (self $property): bool => $property->$access);

        if ($names === null) {
            return array_filter($properties, fn (self $property): bool => $property->appliesTo($elementType));
        }

        $only = [];

        foreach ($names as $name) {
            $only[$name] = $properties[$name] ?? throw new LogicException("[$elementType] does not declare a $access element property [$name].");
        }

        return $only;
    }
}
