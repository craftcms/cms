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
            'section' => Type::listOf(Type::string()),
            'sectionId' => Type::listOf(Type::int()),
            'type' => Type::listOf(Type::string()),
            'typeId' => Type::listOf(Type::int()),
            'authorId' => Type::listOf(Type::boolean()),
            'authorGroup' => Type::listOf(Type::string()),
            'postDate' => Type::string(),
            'before' => Type::string(),
            'after' => Type::string(),
            'expiryDate' => Type::string(),
        ]);
    }
}
