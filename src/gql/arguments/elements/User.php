<?php
namespace craft\gql\arguments\elements;

use GraphQL\Type\Definition\Type;

/**
 * Class User
 */
class User extends BaseElement
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'admin' => Type::boolean(),
            'can' => Type::string(),
            'groupId' => Type::listOf(Type::int()),
            'group' => Type::listOf(Type::string()),
            'email' => Type::listOf(Type::string()),
            'username' => Type::listOf(Type::string()),
            'firstName' => Type::listOf(Type::string()),
            'lastName' => Type::listOf(Type::string()),
            'lastLoginDate' => Type::string(),
        ]);
    }
}
