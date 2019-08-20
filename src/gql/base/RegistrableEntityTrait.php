<?php
namespace craft\gql\base;

use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Trait RegistrableEntityTrait
 */
trait RegistrableEntityTrait
{
    /**
     * Returns an instance of this schema object's type as provided by entity registry
     *
     * @param array $fields optional fields to use
     * @return ObjectType
     */
    public static function getType($fields = null): Type
    {
        return GqlEntityRegistry::getEntity(static::class) ?: GqlEntityRegistry::createEntity(static::class, new ObjectType([
            'name' => static::getName(),
            'fields' => $fields ?: (static::class . '::getFields'),
        ]));
    }
}
