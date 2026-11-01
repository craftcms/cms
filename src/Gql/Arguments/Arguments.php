<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Enums\ElementQueryParamType;
use CraftCms\Cms\Element\Queries\Attributes\ElementQueryParam;
use CraftCms\Cms\Gql\Types\QueryArgument;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\Type;
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
                'type' => match ($param->type) {
                    ElementQueryParamType::String => Type::string(),
                    ElementQueryParamType::StringOrStrings,
                    ElementQueryParamType::NullableStringOrStrings,
                    ElementQueryParamType::StringList => Type::listOf(Type::string()),
                    ElementQueryParamType::Int => Type::int(),
                    ElementQueryParamType::IntOrInts,
                    ElementQueryParamType::ScalarOrScalars,
                    ElementQueryParamType::Array => Type::listOf(QueryArgument::getType()),
                    ElementQueryParamType::Boolean,
                    ElementQueryParamType::NullableBoolean => Type::boolean(),
                    ElementQueryParamType::Scalar => QueryArgument::getType(),
                },
                'description' => $param->descriptionFor($elementType),
            ];
        }

        return $arguments;
    }
}
