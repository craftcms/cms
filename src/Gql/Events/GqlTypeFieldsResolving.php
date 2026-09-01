<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

use GraphQL\Type\Definition\FieldDefinition;

/**
 * @event GqlTypeFieldsResolving The event that is triggered when defining GraphQL type fields.
 */
/** @phpstan-import-type FieldDefinitionConfig from FieldDefinition */
class GqlTypeFieldsResolving
{
    public function __construct(
        /** @var array<string, FieldDefinitionConfig> */
        public array $fields,
        public string $typeName,
    ) {}
}
