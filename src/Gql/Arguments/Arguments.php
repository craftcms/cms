<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Queries\Attributes\ElementQueryParam;
use CraftCms\Cms\Gql\Attributes\Gql;
use CraftCms\Cms\Gql\ElementSchemaTypes;
use GraphQL\Type\Definition\Argument;
use LogicException;

/**
 * @phpstan-import-type ArgumentConfig from Argument
 *
 * @since 6.0.0
 */
abstract class Arguments
{
    /** @return array<string, ArgumentConfig> */
    public static function getArguments(): array
    {
        return static::queryParamArguments(Element::class, ['id', 'uid']);
    }

    /** @return array<string, ArgumentConfig> */
    public static function getContentArguments(): array
    {
        return [];
    }

    /**
     * Returns arguments for the query params of an element type’s query that apply to it and that {@see Gql} doesn’t
     * exclude, if their gates allow the element type.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @return array<string, ArgumentConfig>
     */
    protected static function gqlQueryParamArguments(string $elementType): array
    {
        $params = array_filter(
            ElementQueryParam::forElementType($elementType),
            fn (ElementQueryParam $param): bool => (bool) Gql::of($param)?->allows($elementType),
        );

        return static::queryParamArguments($elementType, array_keys($params));
    }

    /**
     * Returns arguments for params that an element type's query declares with {@see ElementQueryParam}.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  list<string>  $names  Param names to return, in this order. Every name must be declared.
     * @return array<string, ArgumentConfig>
     */
    protected static function queryParamArguments(string $elementType, array $names): array
    {
        $params = ElementQueryParam::all($elementType::find()::class);
        $arguments = [];

        foreach ($names as $name) {
            $param = $params[$name] ?? throw new LogicException("[$elementType] does not declare the query param [$name].");
            $arguments[$name] = [
                'name' => $name,
                'type' => ElementSchemaTypes::queryParam($param->type),
                'description' => $param->descriptionFor($elementType),
            ];
        }

        return $arguments;
    }
}
