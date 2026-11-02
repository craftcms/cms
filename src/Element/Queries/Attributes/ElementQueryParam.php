<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Attributes;

use Attribute;
use CraftCms\Cms\Element\Attributes\Concerns\DescribesMember;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Enums\ElementCapability;
use CraftCms\Cms\Element\Enums\ElementQueryParamType;
use ReflectionClass;
use ReflectionMethod;

/**
 * Describes an element query setter as a param that external APIs can expose.
 *
 * Descriptions shared by several element types can use `{element}` and `{elements}` placeholders for the element
 * type's lowercase singular and plural names. Overriding methods inherit the attribute from the method they override.
 *
 * @since 6.0.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
class ElementQueryParam
{
    use DescribesMember;

    /** @var array<class-string, array<string, self>> */
    private static array $params = [];

    /**
     * @param  ElementCapability|null  $requires  The capability an element type needs for the param to apply to it.
     */
    public function __construct(
        public readonly ElementQueryParamType $type,
        public readonly string $description,
        public readonly ?ElementCapability $requires = null,
    ) {}

    /**
     * Returns the params an element type’s query declares that apply to the element type, indexed by setter name.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @return array<string, self>
     */
    public static function forElementType(string $elementType): array
    {
        return array_filter(self::all($elementType::find()::class), fn (self $param): bool => $param->appliesTo($elementType));
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
            if (! $method->isStatic() && ($declaration = self::declarationOf($method))) {
                $params[$method->getName()] = self::fromDeclaration($declaration);
            }
        }

        return self::$params[$queryClass] = $params;
    }
}
