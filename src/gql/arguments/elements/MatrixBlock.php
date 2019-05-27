<?php
namespace craft\gql\arguments\elements;

use GraphQL\Type\Definition\Type;

/**
 * Class MatrixBlock
 */
class MatrixBlock extends BaseElement
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'fieldId' => Type::listOf(Type::int()),
            'ownerId' => Type::listOf(Type::string()),
            'ownerSiteId' => Type::listOf(Type::int()),
            'typeId' =>Type::listOf(Type::int()),
            'type' => Type::listOf(Type::string()),
        ]);
    }
}
