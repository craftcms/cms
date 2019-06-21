<?php
namespace craft\gql\queries;

use craft\gql\arguments\elements\GlobalSet as GlobalSetArguments;
use craft\gql\interfaces\elements\GlobalSet as GlobalSetInterface;
use craft\gql\resolvers\elements\GlobalSet as GlobalSetResolver;
use craft\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\Type;

/**
 * Class GlobalSet
 */
class GlobalSet
{
    /**
     * @inheritdoc
     */
    public static function getQueries(): array
    {
        if (!GqlHelper::canQueryGlobalSets()) {
            return [];
        }

        return [
            'queryGlobalSets' => [
                'type' => Type::listOf(GlobalSetInterface::getType()),
                'args' => GlobalSetArguments::getArguments(),
                'resolve' => GlobalSetResolver::class . '::resolve',
            ],
        ];
    }
}