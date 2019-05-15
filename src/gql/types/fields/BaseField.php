<?php
namespace craft\gql\types\fields;

use craft\gql\common\SchemaObject;
use craft\gql\types\FieldGroup;
use GraphQL\Type\Definition\Type;

/**
 * Class BaseField
 */
abstract class BaseField extends SchemaObject
{
    /**
     * A list of common fields.
     *
     * @return array
     */
    protected static function getBaseFields(): array
    {
        return array_merge(parent::getCommonFields(), [
            'fieldGroup' => FieldGroup::getType(),
            'name' => Type::nonNull(Type::string()),
            'handle' => Type::nonNull(Type::string()),
            'context' => Type::nonNull(Type::string()),
            'instructions' => Type::string(),
            'searchable' => Type::nonNull(Type::boolean()),
            'translationMethod' => Type::nonNull(Type::string()),
            'translationKeyFormat' => Type::string(),
            'fieldType' => [
                'name' => 'fieldType',
                'type' => Type::nonNull(Type::string()),
                'resolve' => function ($rootValue) {
                    return get_class($rootValue);
                }
            ]
        ]);
    }
}
