<?php
namespace craft\gql\types\fields;

use GraphQL\Type\Definition\Type;

/**
 * Class BaseRelationField
 */
abstract class BaseRelationField extends BaseField
{
    /**
     * @inheritdoc
     */
    public static function getCommonFields(): array
    {
        return array_merge(parent::getCommonFields(), [
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
