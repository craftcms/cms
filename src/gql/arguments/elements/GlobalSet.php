<?php

namespace craft\gql\arguments\elements;

use craft\gql\base\ElementArguments;
use GraphQL\Type\Definition\Type;

/**
 * Class GlobalSet
 */
class GlobalSet extends ElementArguments
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
