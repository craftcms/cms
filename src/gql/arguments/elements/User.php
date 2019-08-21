<?php

namespace craft\gql\arguments\elements;

use craft\gql\base\ElementArguments;
use GraphQL\Type\Definition\Type;

/**
 * Class User
 */
class User extends ElementArguments
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'email' => [
                'name' => 'email',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the users’ email addresses.'
            ],
            'username' => [
                'name' => 'username',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the users’ usernames.'
            ],
            'firstName' => [
                'name' => 'firstName',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the users’ first names.'
            ],
            'lastName' => [
                'name' => 'lastName',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the users’ last names.'
            ],
        ]);
    }
}
