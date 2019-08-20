<?php
namespace craft\gql\base;

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
     * List of fields for this type.
     *
     * @return array
     */
    public static function getFields(): array
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
     * Returns the schema object name
     *
     * @return string
     */
    abstract public static function getName(): string;
}
