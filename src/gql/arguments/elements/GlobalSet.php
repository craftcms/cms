<?php
namespace craft\gql\arguments\elements;

use GraphQL\Type\Definition\Type;

/**
 * Class GlobalSet
 */
class GlobalSet extends BaseElement
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'editable' => Type::boolean(),
            'handle' => Type::listOf(Type::string()),
        ]);
    }
}
