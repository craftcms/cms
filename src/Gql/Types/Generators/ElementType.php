<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Generators;

use CraftCms\Cms\Gql\Contracts\GeneratorInterface;
use CraftCms\Cms\Gql\Contracts\SingleGeneratorInterface;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\Interfaces\Element as ElementInterface;
use CraftCms\Cms\Gql\Types\Elements\Element;
use CraftCms\Cms\Gql\Types\ObjectType;
use CraftCms\Cms\Support\Facades\Gql;

class ElementType implements GeneratorInterface, SingleGeneratorInterface
{
    public static function generateTypes(mixed $context = null): array
    {
        // Base elements have no context
        $type = static::generateType(null);

        return [$type->name => $type];
    }

    public static function generateType(mixed $context): ObjectType
    {
        $typeName = 'Element';

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new Element([
            'name' => $typeName,
            'fields' => function () use ($typeName) {
                $elementFields = ElementInterface::getFieldDefinitions();

                return Gql::prepareFieldDefinitions($elementFields, $typeName);
            },
        ]));
    }
}
