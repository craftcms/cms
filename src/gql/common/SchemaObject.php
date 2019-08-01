<?php
namespace craft\gql\common;

use craft\gql\GqlEntityRegistry;
use craft\gql\types\DateTime;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class SchemaObject
 */
abstract class SchemaObject
{
    /**
     * A list of common fields.
     *
     * @return array
     */
    public static function getCommonFields(): array
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
            'dateCreated' => [
                'name' => 'dateCreated',
                'type' => DateTime::getType(),
                'description' => 'The date the entity was created'
            ],
            'dateUpdated' => [
                'name' => 'dateUpdated',
                'type' => DateTime::getType(),
                'description' => 'The date the entity was last updated'
            ],
        ];
    }

    /**
     * Returns an instance of this schema object's type.
     *
     * @param array $fields optional fields to use
     * @return Type
     */
    public static function getType($fields = null): Type
    {
        return GqlEntityRegistry::getEntity(static::class) ?: GqlEntityRegistry::createEntity(static::class, new ObjectType([
            'name' => static::getName(),
            'fields' => $fields ?: (static::class . '::getFields'),
        ]));
    }

    /**
     * Returns the fields configured for this type.
     *
     * @return array
     */
    abstract public static function getFields(): array;

    /**
     * Returns the schema object name
     *
     * @return string
     */
    abstract public static function getName(): string;
}
