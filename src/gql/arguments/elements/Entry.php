<?php
namespace craft\gql\arguments\elements;

use GraphQL\Type\Definition\Type;

/**
 * Class Entry
 */
class Entry extends StructureElement
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'editable' => Type::boolean(),
            'section' => Type::string(),
            'sectionId' => Type::int(),
            'type' => Type::string(),
            'typeId' => Type::int(),
            'authorId' => Type::boolean(),
            'authorGroup' => Type::string(),
            'authorGroupId' => Type::int(),
            'postDate' => Type::string(),
            'before' => Type::string(),
            'after' => Type::string(),
            'expiryDate' => Type::string(),
        ]);
    }
}
