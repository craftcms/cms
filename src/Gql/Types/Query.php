<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types;

use CraftCms\Cms\Gql\Concerns\HasGqlType;
use CraftCms\Cms\Gql\Exceptions\GqlException;

class Query
{
    use HasGqlType;

    public static function getFieldDefinitions(): array
    {
        throw new GqlException('Query type should not have any fields listed statically. Fields must be set at type register time.');
    }

    public static function getName(): string
    {
        return 'Query';
    }
}
