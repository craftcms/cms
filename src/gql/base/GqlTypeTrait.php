<?php
namespace craft\gql\base;

use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\ObjectType as GqlObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Trait GqlTypeTrait
 */
trait GqlTypeTrait
{
    /**
     * List of fields for this type.
     *
     * @return array
     */
    public static function getFieldDefinitions(): array
    {
        return self::getCommonFieldDefinitions();
    }

    /**
     * List of common fields for all types.
     * @TODO Really, this is just a workaround for inheritance using traits. See craft\gql\types\Volume
     *
     * @return array
     */
    public static function getCommonFieldDefinitions(): array
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::id(),
                'description' => 'The id of the entity'
            ],
            'uid' => [
                'name' => 'uid',
                'type' => Type::string(),
                'description' => 'The uid of the entity'
            ],
        ];
    }

    /**
     * Returns an instance of this schema object's type as provided by entity registry
     *
     * @param array $fields optional fields to use
     * @return GqlObjectType
     */
    public static function getType($fields = null): Type
    {
        return GqlEntityRegistry::getEntity(static::class) ?: GqlEntityRegistry::createEntity(static::class, new GqlObjectType([
            'name' => static::getName(),
            'fields' => $fields ?: (static::class . '::getFieldDefinitions'),
        ]));
    }
}
