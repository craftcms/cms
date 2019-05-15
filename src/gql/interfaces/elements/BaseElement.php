<?php
namespace craft\gql\interfaces\elements;

use craft\gql\common\SchemaObject;
use GraphQL\Type\Definition\Type;

/**
 * Class Element
 */
abstract class BaseElement extends SchemaObject
{
    /**
     * @inheritdoc
     */
    public static function getCommonFields(): array
    {
        // Todo fieldLayout, structure and structureNode (root, lft, rgt, level)
        return array_merge(parent::getCommonFields(), [
            'title' => Type::string(),
            'slug' => Type::string(),
            'uri' => Type::string(),
            'enabled' => Type::boolean(),
            'archived' => Type::boolean(),
            'siteUid' => Type::boolean(),
            'searchScore' => Type::string(),
            'trashed' => Type::boolean(),
            'elementType' => Type::string()
        ]);
    }
}
