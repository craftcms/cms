<?php
namespace craft\gql\queries;

use craft\gql\arguments\elements\GlobalSet as GlobalSetArguments;
use craft\gql\base\Query;
use craft\gql\interfaces\elements\GlobalSet as GlobalSetInterface;
use craft\gql\resolvers\elements\GlobalSet as GlobalSetResolver;
use craft\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\Type;

/**
 * Class GlobalSet
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
            'queryGlobalSets' => [
                'type' => Type::listOf(GlobalSetInterface::getType()),
                'args' => GlobalSetArguments::getArguments(),
                'resolve' => GlobalSetResolver::class . '::resolve',
                'description' => 'This query is used to query for global sets.'
            ],
        ];
    }
}