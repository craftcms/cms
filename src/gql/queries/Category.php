<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\queries;

use craft\gql\arguments\elements\Category as CategoryArguments;
use craft\gql\base\Query;
use craft\gql\interfaces\elements\Category as CategoryInterface;
use craft\gql\resolvers\elements\Category as CategoryResolver;
use craft\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\Type;

/**
 * Class Category
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Category extends Query
{
    /**
     * @inheritdoc
     */
    public static function getQueries($checkToken = true): array
    {
        if ($checkToken && !GqlHelper::canQueryCategories()) {
            return [];
        }

        return [
            'categories' => [
                'type' => Type::listOf(CategoryInterface::getType()),
                'args' => CategoryArguments::getArguments(),
                'resolve' => CategoryResolver::class . '::resolve',
                'description' => 'This query is used to query for categories.'
            ],
        ];
    }
}