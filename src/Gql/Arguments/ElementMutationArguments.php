<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments;

use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\Type;

/** @phpstan-import-type ArgumentConfig from Argument */
abstract class ElementMutationArguments extends MutationArguments
{
    /** @return array<string, ArgumentConfig> */
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
