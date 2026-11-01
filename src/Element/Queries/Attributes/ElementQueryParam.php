<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Attributes;

use Attribute;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Enums\ElementQueryParamType;
use ReflectionClass;
use ReflectionMethod;

/**
 * Describes an element query setter as a param that external APIs can expose.
 *
 * Descriptions shared by several element types can use an `{elements}` placeholder for the element type's plural
 * lowercase name. Overriding methods inherit the attribute from the method they override.
 *
 * @since 6.0.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
class ElementQueryParam
{
    /** @var array<class-string, array<string, self>> */
    private static array $params = [];

    public function __construct(
        public readonly ElementQueryParamType $type,
        public readonly string $description,
    ) {}

    /**
     * Returns the description for an element type, replacing `{elements}` with its plural lowercase name.
     *
     * @param  class-string<ElementInterface>  $elementType
     */
    public function descriptionFor(string $elementType): string
    {
        return str_replace('{elements}', $elementType::pluralLowerDisplayName(), $this->description);
    }

    /**
     * Returns the params a query class declares, indexed by setter name.
     *
     * @param  class-string  $queryClass
     * @return array<string, self>
     */
    public static function all(string $queryClass): array
    {
        if (isset(self::$params[$queryClass])) {
            return self::$params[$queryClass];
        }

        $params = [];

        foreach (new ReflectionClass($queryClass)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (! $method->isStatic() && ($param = self::declaredOn($method))) {
                $params[$method->getName()] = $param;
            }
        }

        return self::$params[$queryClass] = $params;
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
