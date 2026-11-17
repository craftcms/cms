<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Generators;

use CraftCms\Cms\Gql\Contracts\GeneratorInterface;
use CraftCms\Cms\Gql\Contracts\SingleGeneratorInterface;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\Types\ColorData;
use CraftCms\Cms\Gql\Types\ObjectType;
use CraftCms\Cms\Support\Facades\Gql;
use GraphQL\Type\Definition\Type;

/**
 * @since 6.0.0
 */
class ColorDataType implements GeneratorInterface, SingleGeneratorInterface
{
    public static function generateTypes(mixed $context = null): array
    {
        return [static::generateType($context)];
    }

    public static function getName(): string
    {
        return 'ColorData';
    }

    public static function generateType(mixed $context): ObjectType
    {
        $typeName = self::getName();

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new ColorData([
            'name' => $typeName,
            'fields' => fn () => Gql::prepareFieldDefinitions([
                'hex' => [
                    'name' => 'hex',
                    'type' => Type::string(),
                    'args' => [
                        'withAlpha' => [
                            'name' => 'withAlpha',
                            'type' => Type::boolean(),
                            'defaultValue' => true,
                            'description' => 'Whether to include the alpha channel, if the color isn’t fully opaque.',
                        ],
                    ],
                ],
                'label' => Type::string(),
                'red' => Type::int(),
                'green' => Type::int(),
                'blue' => Type::int(),
                'alpha' => Type::float(),
                'hue' => Type::int(),
                'saturation' => Type::int(),
                'lightness' => Type::int(),
                'luma' => Type::float(),
                'rgb' => Type::string(),
                'rgba' => Type::string(),
                'rgbChannels' => Type::string(),
                'hsl' => Type::string(),
                'hsla' => Type::string(),
                'oklch' => Type::string(),
                'contrastColor' => Type::string(),
                'contrastRatio' => [
                    'name' => 'contrastRatio',
                    'type' => Type::float(),
                    'args' => [
                        'with' => [
                            'name' => 'with',
                            'type' => Type::nonNull(Type::string()),
                            'description' => 'The hex value of the color to compare with.',
                        ],
                    ],
                ],
            ], $typeName),
        ]));
    }
}
