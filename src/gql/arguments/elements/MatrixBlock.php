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
            'fieldId' => Type::int(),
            'ownerId' => Type::string(),
            'ownerSiteId' => Type::int(),
            'typeId' => Type::int(),
            'type' => Type::string(),
        ]);
    }
}
