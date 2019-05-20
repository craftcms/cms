<?php
namespace craft\gql\types;

use craft\gql\common\SchemaObject;
use craft\gql\TypeRegistry;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class Structure
 */
class Structure extends SchemaObject
{
    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'Structure';
    }

    /**
     * @inheritdoc
     */
    public static function getFields(): array
    {
        return array_merge(parent::getCommonFields(), [
            'maxLevels' => Type::int(),
            'root' => StructureNode::getType(),
        ]);
    }
}
