<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Gql\Events\DefineGqlTypeFields;

class TypeManager extends Component
{
    public function registerFieldDefinitions(array $fields, string $typeName): array
    {
        event($event = new DefineGqlTypeFields(
            fields: $fields,
            typeName: $typeName,
        ));

        return $event->fields;
    }
}
