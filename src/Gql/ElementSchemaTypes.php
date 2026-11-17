<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

use CraftCms\Cms\Element\Enums\ElementPropertyType;
use CraftCms\Cms\Element\Enums\ElementQueryParamType;
use CraftCms\Cms\Gql\Types\DateTime;
use CraftCms\Cms\Gql\Types\QueryArgument;
use GraphQL\Type\Definition\Type;

/**
 * Maps element query param and property types to GraphQL types.
 *
 * @since 6.0.0
 */
class ElementSchemaTypes
{
    public static function queryParam(ElementQueryParamType $type): Type
    {
        return match ($type) {
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
        };
    }

    /**
     * @param  bool  $input  Whether the type is for a mutation argument rather than a field. IDs are `ID` in mutation
     *                       arguments and `Int` in fields.
     */
    public static function property(ElementPropertyType $type, bool $input): Type
    {
        return match ($type) {
            ElementPropertyType::String => Type::string(),
            ElementPropertyType::Float => Type::float(),
            ElementPropertyType::Int => Type::int(),
            ElementPropertyType::Id => $input ? Type::id() : Type::int(),
            ElementPropertyType::IdList => Type::listOf($input ? Type::id() : Type::int()),
            ElementPropertyType::Boolean => Type::boolean(),
            ElementPropertyType::DateTime => DateTime::getType(),
        };
    }
}
