<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Generators;

use CraftCms\Cms\Gql\Contracts\GeneratorInterface;
use CraftCms\Cms\Gql\Contracts\SingleGeneratorInterface;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\Types\IconData;
use CraftCms\Cms\Gql\Types\ObjectType;
use CraftCms\Cms\Support\Facades\Gql;
use GraphQL\Type\Definition\Type;

class IconDataType implements GeneratorInterface, SingleGeneratorInterface
{
    public static function generateTypes(mixed $context = null): array
    {
        return [static::generateType($context)];
    }

    public static function getName(): string
    {
        return 'IconData';
    }

    public static function generateType(mixed $context): ObjectType
    {
        $typeName = self::getName();

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new IconData([
            'name' => $typeName,
            'fields' => function () use ($typeName) {
                $fields = [
                    'name' => Type::string(),
                    'styles' => Type::listOf(Type::string()),
                ];

                return Gql::prepareFieldDefinitions($fields, $typeName);
            },
        ]));
    }
}
