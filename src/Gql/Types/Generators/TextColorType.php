<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Generators;

use CraftCms\Cms\Gql\Contracts\GeneratorInterface;
use CraftCms\Cms\Gql\Contracts\SingleGeneratorInterface;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\Types\ObjectType;
use CraftCms\Cms\Gql\Types\TextColor;
use CraftCms\Cms\Support\Facades\Gql;
use GraphQL\Type\Definition\Type;

/**
 * @since 6.0.0
 */
class TextColorType implements GeneratorInterface, SingleGeneratorInterface
{
    public static function generateTypes(mixed $context = null): array
    {
        return [static::generateType($context)];
    }

    public static function getName(): string
    {
        return 'TextColor';
    }

    public static function generateType(mixed $context): ObjectType
    {
        $typeName = self::getName();

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new TextColor([
            'name' => $typeName,
            'fields' => function () use ($typeName) {
                $fields = [
                    'color' => [
                        'name' => 'color',
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'The text color, `#ffffff` or `#000000`.',
                    ],
                    'contrast' => [
                        'name' => 'contrast',
                        'type' => Type::nonNull(Type::float()),
                        'description' => 'The WCAG contrast ratio of the text color with the part of the image behind it. Under 4.5 means the text needs a scrim or a shadow behind it.',
                    ],
                ];

                return Gql::prepareFieldDefinitions($fields, $typeName);
            },
        ]));
    }
}
