<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Gql\Events\GqlTypeFieldsResolving;
use GraphQL\Type\Definition\FieldDefinition;

/** @phpstan-import-type FieldDefinitionConfig from FieldDefinition */
class TypeManager extends Component
{
    /**
     * @param  array<string, FieldDefinitionConfig>  $fields
     * @return array<string, FieldDefinitionConfig>
     */
    public function registerFieldDefinitions(array $fields, string $typeName): array
    {
        event($event = new GqlTypeFieldsResolving(
            fields: $fields,
            typeName: $typeName,
        ));

        return $event->fields;
    }
}
