<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Input;

use CraftCms\Cms\Gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * @since 6.0.0
 */
class LinkData extends InputObjectType
{
    public static function getType(): InputObjectType
    {
        $typeName = 'LinkDataInput';

        /** @var InputObjectType $type */
        $type = GqlEntityRegistry::getOrCreate($typeName, fn () => new InputObjectType([
            'name' => $typeName,
            'fields' => [
                'type' => Type::string(),
                'value' => Type::string(),
                'label' => Type::string(),
                'urlSuffix' => Type::string(),
            ],
        ]));

        return $type;
    }
}
