<?php
namespace craft\gql\arguments\elements;

use craft\elements\Entry as EntryElement;
use craft\gql\arguments\elements\StructureElement;
use craft\gql\TypeLoader;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\DateTimeType;
use craft\gql\types\generators\EntryTypeGenerator;
use GraphQL\Type\Definition\InterfaceType;
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
