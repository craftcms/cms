<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Schema;

use CraftCms\Cms\Element\Enums\ElementPropertyType;
use CraftCms\Cms\Element\Enums\ElementQueryParamType;
use CraftCms\Cms\Mcp\ElementQueryCriteria;

/**
 * Maps element query param and property types to JSON Schema.
 *
 * @since 6.0.0
 */
class ElementSchemaTypes
{
    private const string NumericParamToken = '((not |!=|<=|>=|<|>|=)?\s*-?\d+|:empty:|:notempty:|not :empty:)';

    /** A numeric query param string, such as `'not 1'`, `'>= 3'`, `'1, 2'`, or `':empty:'`. */
    private const string NumericParamPattern = '^\s*'.self::NumericParamToken.'(\s*,\s*'.self::NumericParamToken.')*\s*$';

    /** @return array<string, mixed> */
    public static function queryParam(ElementQueryParamType $type): array
    {
        return match ($type) {
            ElementQueryParamType::String => ['type' => 'string'],
            ElementQueryParamType::StringOrStrings => ElementQueryCriteria::StringOrStringsSchema,
            ElementQueryParamType::NullableStringOrStrings => ['anyOf' => [...ElementQueryCriteria::StringOrStringsSchema['anyOf'], ['type' => 'null']]],
            ElementQueryParamType::StringList => ['type' => 'array', 'items' => ['type' => 'string']],
            ElementQueryParamType::Int => ['type' => 'integer'],
            ElementQueryParamType::IntOrInts => ['anyOf' => [
                ['type' => 'integer'],
                ['type' => 'string', 'pattern' => self::NumericParamPattern],
                ['type' => 'array', 'items' => ['anyOf' => [
                    ['type' => ['integer', 'null']],
                    ['type' => 'string', 'pattern' => self::NumericParamPattern],
                    ['enum' => ['and', 'or', 'not']],
                ]]],
            ]],
            ElementQueryParamType::Boolean => ['type' => 'boolean'],
            ElementQueryParamType::NullableBoolean => ['type' => ['boolean', 'null']],
            ElementQueryParamType::Scalar => ['type' => ['integer', 'string', 'boolean']],
            ElementQueryParamType::ScalarOrScalars => ['anyOf' => [
                ['type' => ['integer', 'string']],
                ['type' => 'array', 'items' => ['type' => ['integer', 'string']]],
            ]],
            ElementQueryParamType::Array => ['type' => 'array'],
        };
    }

    /** @return array<string, mixed> */
    public static function property(ElementPropertyType $type, bool $nullable): array
    {
        $schema = match ($type) {
            ElementPropertyType::String => ['type' => 'string'],
            ElementPropertyType::Int, ElementPropertyType::Id => ['type' => 'integer'],
            ElementPropertyType::IdList => ['type' => 'array', 'items' => ['type' => 'integer']],
            ElementPropertyType::Boolean => ['type' => 'boolean'],
            ElementPropertyType::DateTime => ['type' => 'string', 'format' => 'date-time'],
            ElementPropertyType::Float => ['type' => ['number', 'string']],
        };

        if ($nullable) {
            $schema['type'] = [...(array) $schema['type'], 'null'];
        }

        return $schema;
    }
}
