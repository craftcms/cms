<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments;

use GraphQL\Type\Definition\Type;

class Transform extends Arguments
{
    #[\Override]
    public static function getArguments(): array
    {
        return [
            'handle' => [
                'name' => 'handle',
                'type' => Type::string(),
                'description' => 'The handle of the named transform to use.',
            ],
            'transform' => [
                'name' => 'transform',
                'type' => Type::string(),
                'description' => 'The handle of the named transform to use.',
            ],
            'width' => [
                'name' => 'width',
                'type' => Type::int(),
                'description' => 'Width for the generated transform',
            ],
            'height' => [
                'name' => 'height',
                'type' => Type::int(),
                'description' => 'Height for the generated transform',
            ],
            'mode' => [
                'name' => 'mode',
                'type' => Type::string(),
                'description' => 'The mode to use for the generated transform.',
            ],
            'position' => [
                'name' => 'position',
                'type' => Type::string(),
                'description' => 'The position to use when cropping, if no focal point specified.',
            ],
            'interlace' => [
                'name' => 'interlace',
                'type' => Type::string(),
                'description' => 'The interlace mode to use for the transform',
            ],
            'quality' => [
                'name' => 'quality',
                'type' => Type::int(),
                'description' => 'The quality of the transform',
            ],
            'format' => [
                'name' => 'format',
                'type' => Type::string(),
                'description' => 'The format to use for the transform',
            ],
            'immediately' => [
                'name' => 'immediately',
                'type' => Type::boolean(),
                'description' => '[_Deprecated_] This argument is deprecated and has no effect.',
            ],
        ];
    }
}
