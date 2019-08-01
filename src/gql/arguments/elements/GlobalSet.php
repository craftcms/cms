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
            'handle' => [
                'name' => 'handle',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the global sets’ handles.'
            ],
        ]);
    }
}
