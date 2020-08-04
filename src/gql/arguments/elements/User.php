<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\arguments\elements;

use Craft;
use craft\elements\User as UserElement;
use craft\fields\Matrix;
use craft\gql\base\ElementArguments;
use craft\gql\types\QueryArgument;
use GraphQL\Type\Definition\Type;

/**
 * Class User
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class User extends ElementArguments
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), self::getContentArguments(), [
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
            'hasPhoto' => [
                'name' => 'hasPhoto',
                'type' => Type::boolean(),
                'description' => 'Narrows the query results to only users that have (or don’t have) a user photo.',
            ],
            'groupId' => [
                'name' => 'groupId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the user group the users belong to, per the groups’ IDs.'
            ],
            'group' => [
                'name' => 'group',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the user group the users belong to.'
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getContentArguments(): array
    {
        $contentArguments = [];
        $contentFields = Craft::$app->getFields()->getLayoutByType(UserElement::class)->getFields();

        foreach ($contentFields as $contentField) {
            if (!$contentField instanceof Matrix) {
                $contentArguments[$contentField->handle] = [
                    'name' => $contentField->handle,
                    'type' => Type::listOf(QueryArgument::getType()),
                ];
            }
        }

        return array_merge(parent::getContentArguments(), $contentArguments);
    }
}
