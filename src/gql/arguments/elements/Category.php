<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\arguments\elements;

use Craft;
use craft\elements\Category as CategoryElement;
use craft\gql\base\StructureElementArguments;
use craft\gql\types\QueryArgument;
use GraphQL\Type\Definition\Type;

/**
 * Class Category
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Category extends StructureElementArguments
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), self::getContentArguments(), [
            'editable' => [
                'name' => 'editable',
                'type' => Type::boolean(),
                'description' => 'Whether to only return categories that the user has permission to edit.'
            ],
            'group' => [
                'name' => 'group',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the category groups the categories belong to per the group’s handles.'
            ],
            'groupId' => [
                'name' => 'groupId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the category groups the categories belong to, per the groups’ IDs.'
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getContentArguments(): array
    {
        $categoryGroupFieldArguments = Craft::$app->getGql()->getContentArguments(Craft::$app->getCategories()->getAllGroups(), CategoryElement::class);
        return array_merge(parent::getContentArguments(), $categoryGroupFieldArguments);
    }
}
