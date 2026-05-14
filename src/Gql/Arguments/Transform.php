<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments;

use CraftCms\Cms\Asset\Exceptions\ImageTransformException;
use CraftCms\Cms\Support\Facades\AssetTransforms;
use GraphQL\Type\Definition\Type;

class Transform extends Arguments
{
    #[\Override]
    public static function getArguments(): array
    {
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
            'transformer' => [
                'name' => 'transformer',
                'type' => Type::string(),
                'description' => 'The transformer handle to use for the transform',
            ],
            'immediately' => [
                'name' => 'immediately',
                'type' => Type::boolean(),
                'description' => 'Whether the transform should be generated immediately or only when the image is requested used the generated URL',
            ],
        ];

        foreach (AssetTransforms::getAllAssetTransformers() as $transformerHandle => $transformerClass) {
            foreach ($transformerClass::gqlArguments() as $name => $argument) {
                if (isset($arguments[$name])) {
                    throw new ImageTransformException("The `$transformerHandle` asset transformer defines a GraphQL transform argument that already exists: $name");
                }

                $arguments[$name] = [
                    'name' => $name,
                    ...$argument,
                ];
            }
        }

        return $arguments;
    }
}
