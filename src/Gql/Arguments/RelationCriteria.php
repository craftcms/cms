<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments;

use GraphQL\Type\Definition\Type;

class RelationCriteria extends Arguments
{
    #[\Override]
    public static function getArguments(): array
    {
        return [
            'relatedViaField' => [
                'name' => 'relatedViaField',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the relations based on the field they were created in.',
            ],
            'relatedViaSite' => [
                'name' => 'relatedViaSite',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the relations based on the site they were created in.',
            ],
        ];
    }
}
