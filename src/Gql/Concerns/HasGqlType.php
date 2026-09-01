<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Concerns;

use CraftCms\Cms\Gql\GqlEntityRegistry;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType as GqlObjectType;
use GraphQL\Type\Definition\Type;

/** @phpstan-import-type UnnamedFieldDefinitionConfig from FieldDefinition */
trait HasGqlType
{
    /**
     * @param  array<string, UnnamedFieldDefinitionConfig>|null  $fields  optional fields to use
     * @return GqlObjectType
     */
    public static function getType(?array $fields = null): Type
    {
        return GqlEntityRegistry::getOrCreate(static::class, fn () => new GqlObjectType([
            /** @phpstan-ignore-next-line */
            'name' => static::getName(),
            'fields' => $fields ?: (static::class.'::getFieldDefinitions'),
        ]));
    }

    /** @return array<string, UnnamedFieldDefinitionConfig> */
    protected static function getConditionalFields(): array
    {
        return [];
    }
}
