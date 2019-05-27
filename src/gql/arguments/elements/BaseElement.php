<?php
namespace craft\gql\arguments\elements;

use craft\gql\arguments\BaseArguments;
use GraphQL\Type\Definition\Type;

/**
 * Class Element
 */
abstract class BaseElement extends BaseArguments
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'status' => Type::listOf(Type::string()),
            'archived' => Type::boolean(),
            'trashed' => Type::boolean(),
            'site' => Type::listOf(Type::string()),
            'siteId' => Type::string(),
            'unique' => Type::boolean(),
            'enabledForSite' => Type::boolean(),
            'title' => Type::listOf(Type::string()),
            'slug' => Type::listOf(Type::string()),
            'uri' => Type::listOf(Type::string()),
            'search' => Type::string(),
            'ref' => Type::listOf(Type::string()),
            'fixedOrder' => Type::boolean(),
            'inReverse' => Type::boolean(),
        ]);
    }
}
