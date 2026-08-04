<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments;

use GraphQL\Type\Definition\Type;

abstract class ElementMutationArguments extends MutationArguments
{
    #[\Override]
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'title' => [
                'name' => 'title',
                'type' => Type::string(),
                'description' => 'The title of the element.',
            ],
            'enabled' => [
                'name' => 'enabled',
                'type' => Type::boolean(),
                'description' => 'Whether the element should be enabled.',
            ],
        ]);
    }
}
