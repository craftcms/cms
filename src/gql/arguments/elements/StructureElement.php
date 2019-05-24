<?php
namespace craft\gql\arguments\elements;

use GraphQL\Type\Definition\Type;

/**
 * Class StructureElement
 */
abstract class StructureElement extends BaseElement
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'withStructure' => Type::boolean(),
            'structureId' => Type::int(),
            'level' => Type::int(),
            'hasDescendants' => Type::boolean(),
            'ancestorDist' => Type::int(),
            'descendantDist' => Type::int(),
            'leaves' => Type::boolean(),
        ]);
    }
}
