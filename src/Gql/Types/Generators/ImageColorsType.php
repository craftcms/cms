<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Generators;

use CraftCms\Cms\Gql\Contracts\GeneratorInterface;
use CraftCms\Cms\Gql\Contracts\SingleGeneratorInterface;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\Types\ImageColors;
use CraftCms\Cms\Gql\Types\ObjectType;
use CraftCms\Cms\Support\Facades\Gql;
use GraphQL\Type\Definition\Type;

/**
 * @since 6.0.0
 */
class ImageColorsType implements GeneratorInterface, SingleGeneratorInterface
{
    public static function generateTypes(mixed $context = null): array
    {
        return [static::generateType($context)];
    }

    public static function getName(): string
    {
        return 'ImageColors';
    }

    public static function generateType(mixed $context): ObjectType
    {
        $typeName = self::getName();

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new ImageColors([
            'name' => $typeName,
            'fields' => function () use ($typeName) {
                $fields = [
                    'dominant' => [
                        'name' => 'dominant',
                        'type' => Type::string(),
                        'description' => 'The image’s dominant color as a hex string (e.g. `#3a6ea5`), or null if it couldn’t be determined.',
                    ],
                    'grid' => [
                        'name' => 'grid',
                        'type' => Type::nonNull(Type::listOf(Type::nonNull(Type::listOf(Type::nonNull(Type::string()))))),
                        'description' => 'The average colors of the image’s regions, as rows of hex strings from top to bottom, each listed from left to right. Colors of regions that aren’t fully opaque include an alpha channel (e.g. `#3a6ea580`). Empty if the image couldn’t be sampled.',
                    ],
                    'left' => [
                        'name' => 'left',
                        'type' => Type::string(),
                        'description' => 'The average color of the image’s left edge as a hex string, or null if there’s no grid.',
                    ],
                    'right' => [
                        'name' => 'right',
                        'type' => Type::string(),
                        'description' => 'The average color of the image’s right edge as a hex string, or null if there’s no grid.',
                    ],
                    'top' => [
                        'name' => 'top',
                        'type' => Type::string(),
                        'description' => 'The average color of the image’s top edge as a hex string, or null if there’s no grid.',
                    ],
                    'bottom' => [
                        'name' => 'bottom',
                        'type' => Type::string(),
                        'description' => 'The average color of the image’s bottom edge as a hex string, or null if there’s no grid.',
                    ],
                ];

                return Gql::prepareFieldDefinitions($fields, $typeName);
            },
        ]));
    }
}
