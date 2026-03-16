<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments;

use GraphQL\Type\Definition\Type;

class OptionField extends Arguments
{
    #[\Override]
    public static function getArguments(): array
    {
        return [
            'label' => [
                'name' => 'label',
                'type' => Type::boolean(),
                'description' => 'If set to true, will return label instead of the value',
            ],
        ];
    }
}
