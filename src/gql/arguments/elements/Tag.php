<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\arguments\elements;

use Craft;
use craft\elements\Tag as TagElement;
use craft\gql\base\ElementArguments;
use craft\gql\types\QueryArgument;
use GraphQL\Type\Definition\Type;

/**
 * Class Tag
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Tag extends ElementArguments
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), self::getContentArguments(), [
            'group' => [
                'name' => 'group',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the tag groups the tags belong to per the group’s handles.',
            ],
            'groupId' => [
                'name' => 'groupId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the tag groups the tags belong to, per the groups’ IDs.',
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getContentArguments(): array
    {
        $tagGroupFieldArguments = Craft::$app->getGql()->getContentArguments(Craft::$app->getTags()->getAllTagGroups(), TagElement::class);
        return array_merge(parent::getContentArguments(), $tagGroupFieldArguments);
    }

    /**
     * @inheritdoc
     */
    public static function getDraftArguments(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getRevisionArguments(): array
    {
        return [];
    }
}
