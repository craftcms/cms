<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\queries;

use craft\gql\arguments\elements\GlobalSet as GlobalSetArguments;
use craft\gql\base\Query;
use craft\gql\interfaces\elements\GlobalSet as GlobalSetInterface;
use craft\gql\resolvers\elements\GlobalSet as GlobalSetResolver;
use craft\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\Type;

/**
 * Class GlobalSet
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class GlobalSet extends Query
{
    /**
     * @inheritdoc
     */
    public static function getQueries($checkToken = true): array
    {
        if ($checkToken && !GqlHelper::canQueryGlobalSets()) {
            return [];
        }

        return [
            'globalSets' => [
                'type' => Type::listOf(GlobalSetInterface::getType()),
                'args' => GlobalSetArguments::getArguments(),
                'resolve' => GlobalSetResolver::class . '::resolve',
                'description' => 'This query is used to query for global sets.',
                'complexity' => GqlHelper::singleQueryComplexity(),
            ],
            'globalSet' => [
                'type' => GlobalSetInterface::getType(),
                'args' => GlobalSetArguments::getArguments(),
                'resolve' => GlobalSetResolver::class . '::resolveOne',
                'description' => 'This query is used to query for a single global set.',
                'complexity' => GqlHelper::singleQueryComplexity(),
            ],
        ];
    }
}
