<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Generators;

use CraftCms\Cms\Gql\Contracts\GeneratorInterface;
use CraftCms\Cms\Gql\Contracts\SingleGeneratorInterface;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\Types\ImageAnalysis;
use CraftCms\Cms\Gql\Types\ObjectType;
use CraftCms\Cms\Support\Facades\Gql;
use GraphQL\Type\Definition\Type;

/**
 * @since 6.0.0
 */
class ImageAnalysisType implements GeneratorInterface, SingleGeneratorInterface
{
    public static function generateTypes(mixed $context = null): array
    {
        return [static::generateType($context)];
    }

    public static function getName(): string
    {
        return 'ImageAnalysis';
    }

    public static function generateType(mixed $context): ObjectType
    {
        $typeName = self::getName();

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new ImageAnalysis([
            'name' => $typeName,
            'fields' => function () use ($typeName) {
                $fields = [
                    'colors' => [
                        'name' => 'colors',
                        'type' => ImageColorsType::generateType(null),
                        'description' => 'Color data sampled from the image, or null if it hasn’t been sampled yet.',
                    ],
                    'metadata' => [
                        'name' => 'metadata',
                        'type' => ImageMetadataType::generateType(null),
                        'description' => 'Metadata embedded in the image’s file, such as who made it and when, or null if it hasn’t been read.',
                    ],
                    'keywords' => [
                        'name' => 'keywords',
                        'type' => Type::listOf(Type::nonNull(Type::string())),
                        'description' => 'Words and short phrases that describe the image, or null if it hasn’t been tagged.',
                    ],
                    'text' => [
                        'name' => 'text',
                        'type' => Type::string(),
                        'description' => 'Text that appears in the image, or null if it has none or hasn’t been tagged.',
                    ],
                ];

                return Gql::prepareFieldDefinitions($fields, $typeName);
            },
        ]));
    }
}
