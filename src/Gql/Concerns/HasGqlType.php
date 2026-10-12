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
        $name = [static::class, 'getName'];
        $fieldDefinitions = [static::class, 'getFieldDefinitions'];
        if (! is_callable($name) || ($fields === null && ! is_callable($fieldDefinitions))) {
            throw new \InvalidArgumentException('GraphQL object types must define a name and fields.');
        }

        $typeName = $name();
        if (! is_string($typeName)) {
            throw new \InvalidArgumentException('GraphQL type names must be strings.');
        }

        return GqlEntityRegistry::getOrCreate(static::class, fn () => new GqlObjectType([
            'name' => $typeName,
            'fields' => $fields ?? \Closure::fromCallable($fieldDefinitions),
        ]), GqlObjectType::class);
    }

    /** @return array<string, UnnamedFieldDefinitionConfig> */
    protected static function getConditionalFields(): array
    {
        return [];
    }
}
