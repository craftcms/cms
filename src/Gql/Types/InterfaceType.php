<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types;

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use GraphQL\Type\Definition\Type;

abstract class InterfaceType
{
    abstract public static function getName(): string;

    abstract public static function getTypeGenerator(): string;

    public static function resolveElementTypeName(Element $element): string
    {
        return GqlEntityRegistry::prefixTypeName($element->getGqlTypeName());
    }

    public static function getFieldDefinitions(): array
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::id(),
                'description' => 'The ID of the entity',
            ],
            'uid' => [
                'name' => 'uid',
                'type' => Type::string(),
                'description' => 'The UID of the entity',
            ],
        ];
    }
}
