<?php
namespace craft\gql\types\fields;

use craft\gql\common\SchemaObject;
use craft\gql\types\FieldGroup;
use GraphQL\Type\Definition\Type;

/**
 * Class BaseRelationField
 */
abstract class BaseRelationField extends BaseField
{
    /**
     * A list of common fields.
     *
     * @return array
     */
    protected static function getBaseFields(): array
    {
        return array_merge(parent::getBaseFields(), [
            'sources' => [
                'name' => 'sources',
                'type' => Type::listOf(Type::string()),
                'resolve' => function ($value) {
                    return is_array($value->sources) ? $value->sources : [$value->sources];
                }
            ],
            'source' => Type::string(),
            'targetSiteId' => Type::id(),
            'viewMode' => Type::string(),
            'limit' => Type::int(),
            'selectionLabel' => Type::nonNull(Type::boolean()),
            'localizeRelations' => Type::nonNull(Type::boolean()),
        ]);
    }
}
