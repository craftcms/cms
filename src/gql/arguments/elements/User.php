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
            'groupId' => Type::string(),
            'group' => Type::string(),
            'email' => Type::string(),
            'username' => Type::string(),
            'firstName' => Type::string(),
            'lastName' => Type::string(),
            'lastLoginDate' => Type::string(),
        ]);
    }
}
