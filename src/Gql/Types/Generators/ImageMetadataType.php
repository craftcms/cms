<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Generators;

use CraftCms\Cms\Gql\Contracts\GeneratorInterface;
use CraftCms\Cms\Gql\Contracts\SingleGeneratorInterface;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\Types\ImageMetadata;
use CraftCms\Cms\Gql\Types\ObjectType;
use CraftCms\Cms\Support\Facades\Gql;
use GraphQL\Type\Definition\Type;

/**
 * @since 6.0.0
 */
class ImageMetadataType implements GeneratorInterface, SingleGeneratorInterface
{
    public static function generateTypes(mixed $context = null): array
    {
        return [static::generateType($context)];
    }

    public static function getName(): string
    {
        return 'ImageMetadata';
    }

    public static function generateType(mixed $context): ObjectType
    {
        $typeName = self::getName();

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new ImageMetadata([
            'name' => $typeName,
            'fields' => function () use ($typeName) {
                $fields = [
                    'title' => [
                        'name' => 'title',
                        'type' => Type::string(),
                        'description' => 'A short title for the image.',
                    ],
                    'description' => [
                        'name' => 'description',
                        'type' => Type::string(),
                        'description' => 'A description or caption of the image.',
                    ],
                    'altText' => [
                        'name' => 'altText',
                        'type' => Type::string(),
                        'description' => 'Alt text written for the image.',
                    ],
                    'creator' => [
                        'name' => 'creator',
                        'type' => Type::string(),
                        'description' => 'Who made the image, such as the photographer.',
                    ],
                    'credit' => [
                        'name' => 'credit',
                        'type' => Type::string(),
                        'description' => 'How the image should be credited when it’s published.',
                    ],
                    'copyright' => [
                        'name' => 'copyright',
                        'type' => Type::string(),
                        'description' => 'The image’s copyright notice.',
                    ],
                    'keywords' => [
                        'name' => 'keywords',
                        'type' => Type::nonNull(Type::listOf(Type::nonNull(Type::string()))),
                        'description' => 'Keywords the image was tagged with.',
                    ],
                    'dateTaken' => [
                        'name' => 'dateTaken',
                        'type' => Type::string(),
                        'description' => 'When the image was taken or made, as an ISO 8601 date and time, with a UTC offset if the file has one.',
                    ],
                    'camera' => [
                        'name' => 'camera',
                        'type' => Type::string(),
                        'description' => 'The camera’s make and model.',
                    ],
                    // The coordinates themselves are left out, so public schemas don’t give away where images were taken
                    'city' => [
                        'name' => 'city',
                        'type' => Type::string(),
                        'description' => 'The city the image shows or was taken in.',
                    ],
                    'state' => [
                        'name' => 'state',
                        'type' => Type::string(),
                        'description' => 'The state or province the image shows or was taken in.',
                    ],
                    'country' => [
                        'name' => 'country',
                        'type' => Type::string(),
                        'description' => 'The country the image shows or was taken in.',
                    ],
                ];

                return Gql::prepareFieldDefinitions($fields, $typeName);
            },
        ]));
    }
}
