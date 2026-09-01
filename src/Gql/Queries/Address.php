<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Queries;

use CraftCms\Cms\Gql\Arguments\Elements\Address as AddressArguments;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Interfaces\Elements\Address as AddressInterface;
use CraftCms\Cms\Gql\Resolvers\Elements\Address as AddressResolver;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;

/** @phpstan-import-type UnnamedFieldDefinitionConfig from FieldDefinition */
class Address extends Query
{
    /** @return array<string, UnnamedFieldDefinitionConfig> */
    public static function getQueries(bool $checkToken = true): array
    {
        if ($checkToken && ! GqlHelper::canQueryUsers()) {
            return [];
        }

        return [
            'addresses' => [
                'type' => Type::listOf(AddressInterface::getType()),
                'args' => AddressArguments::getArguments(),
                'resolve' => AddressResolver::class.'::resolve',
                'description' => 'This query is used to query for addresses.',
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ],
            'addressCount' => [
                'type' => Type::nonNull(Type::int()),
                'args' => AddressArguments::getArguments(),
                'resolve' => AddressResolver::class.'::resolveCount',
                'description' => 'This query is used to return the number of addresses.',
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ],
            'address' => [
                'type' => AddressInterface::getType(),
                'args' => AddressArguments::getArguments(),
                'resolve' => AddressResolver::class.'::resolveOne',
                'description' => 'This query is used to query for a single address.',
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ],
        ];
    }
}
