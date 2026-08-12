<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types;

use CraftCms\Cms\Gql\Concerns\HasGqlType;
use CraftCms\Cms\Gql\Exceptions\GqlException;
use GraphQL\Type\Definition\FieldDefinition;

/** @phpstan-import-type FieldDefinitionConfig from FieldDefinition */
class Mutation
{
    use HasGqlType;

    /**
     * @throws GqlException if class called incorrectly.
     */
    /** @return array<string, FieldDefinitionConfig> */
    public static function getFieldDefinitions(): array
    {
        throw new GqlException('Mutation type should not have any fields listed statically. Fields must be set at type register time.');
    }

    public static function getName(): string
    {
        return 'Mutation';
    }
}
