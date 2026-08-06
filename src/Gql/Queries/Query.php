<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Queries;

use CraftCms\Cms\Gql\Concerns\HasGqlType;
use GraphQL\Type\Definition\FieldDefinition;

/** @phpstan-import-type UnnamedFieldDefinitionConfig from FieldDefinition */
abstract class Query
{
    use HasGqlType;

    /**
     * @param  bool  $checkToken  Whether the token should be checked for allowed queries.
     *                            Note that passing a parameter to this method is now deprecated. Use [[\craft\helpers\Gql::createFullAccessSchema()]] instead.
     * @return array<string, UnnamedFieldDefinitionConfig>
     */
    abstract public static function getQueries(bool $checkToken = true): array;
}
