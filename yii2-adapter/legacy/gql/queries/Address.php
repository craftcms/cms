<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\queries;

use craft\gql\arguments\elements\Address as AddressArguments;
use craft\gql\base\Query;
use craft\gql\interfaces\elements\Address as AddressInterface;
use craft\gql\resolvers\elements\Address as AddressResolver;
use craft\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\Type;

/**
 * Class Address
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Address extends Query
{
    /**
     * @inheritdoc
     */
    public static function getQueries(bool $checkToken = true): array
    {
        if ($checkToken && !GqlHelper::canQueryUsers()) {
            return [];
        }

        return [
            'addresses' => [
                'type' => Type::listOf(AddressInterface::getType()),
                'args' => AddressArguments::getArguments(),
                'resolve' => AddressResolver::class . '::resolve',
                'description' => 'This query is used to query for addresses.',
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ],
            'addressCount' => [
                'type' => Type::nonNull(Type::int()),
                'args' => AddressArguments::getArguments(),
                'resolve' => AddressResolver::class . '::resolveCount',
                'description' => 'This query is used to return the number of addresses.',
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ],
            'address' => [
                'type' => AddressInterface::getType(),
                'args' => AddressArguments::getArguments(),
                'resolve' => AddressResolver::class . '::resolveOne',
                'description' => 'This query is used to query for a single address.',
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ],
        ];
    }
}
