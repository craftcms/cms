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
            'admin' => [
                'name' => 'admin',
                'type' => Type::boolean(),
                'description' => 'Narrows the query results to only users that have admin accounts.'
            ],
            'can' => [
                'name' => 'can',
                'type' => Type::string(),
                'description' => 'Narrows the query results to only users that have a certain user permission, either directly on the user account or through one of their user groups.'
            ],
            'groupId' => [
                'name' => 'groupId',
                'type' => Type::listOf(Type::int()),
                'description' => 'Narrows the query results based on the user group the users belong to, per the groups’ IDs.'
            ],
            'group' => [
                'name' => 'group',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the user group the users belong to.'
            ],
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
            'lastLoginDate' => [
                'name' => 'lastLoginDate',
                'type' => Type::string(),
                'description' => 'Narrows the query results based on the users’ last login dates.'
            ],
        ]);
    }
}
