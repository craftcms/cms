<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Attributes;

use Attribute;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Enums\ElementPropertyType;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Describes a writable element property, or the setter of one, so that external APIs can accept it.
 *
 * Descriptions shared by several element types can use an `{element}` placeholder for the element type's lowercase
 * name. Overriding setters inherit the attribute from the setter they override.
 *
 * @since 6.0.0
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class ElementProperty
{
    /** @var array<class-string, array<string, self>> */
    private static array $properties = [];

    public function __construct(
        public readonly ElementPropertyType $type,
        public readonly string $description,
        public readonly bool $nullable = false,
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

            if (! $method->isStatic() && str_starts_with($name, 'set') && ($property = self::declaredOn($method))) {
                $properties[lcfirst(substr($name, 3))] = $property;
            }
        }

        return self::$properties[$elementType] = $properties;
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
