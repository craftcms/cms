<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments;

use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Exceptions\InvalidAssetTransformException;
use GraphQL\Type\Definition\Type;

/**
 * @since 6.0.0
 */
class Transform extends Arguments
{
    #[\Override]
    public static function getArguments(): array
    {
        $descriptions = [
            'format' => 'The format to use for the transform',
            'height' => 'Height for the generated transform',
            'interlace' => 'The interlace mode to use for the transform',
            'mode' => 'The mode to use for the generated transform.',
            'position' => 'The position to use when cropping, if no focal point specified.',
            'quality' => 'The quality of the transform',
            'width' => 'Width for the generated transform',
        ];
        $arguments = [
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
            'immediately' => [
                'name' => 'immediately',
                'type' => Type::boolean(),
                'description' => 'Whether to generate the transform before returning its URL, rather than when the URL is first requested. Asset transformers that generate transforms on request, such as `craft`, honor it; `craft` otherwise follows the disk’s `generate_transforms_before_page_load` setting.',
            ],
        ];

        foreach (app(AssetTransformers::class)->coreParameterRules() as $handle => $rules) {
            $arguments[$handle] = [
                'name' => $handle,
                'type' => match ($rules[0]) {
                    'boolean' => Type::boolean(),
                    'integer' => Type::int(),
                    'numeric' => Type::float(),
                    'string' => Type::string(),
                    default => throw new InvalidAssetTransformException('Invalid asset transform parameter type.'),
                },
                'description' => $descriptions[$handle] ?? ucfirst($handle).' for the generated transform.',
            ];
        }

        return $arguments;
    }
}
