<?php
namespace craft\gql\base;

use craft\gql\types\DateTime;
use GraphQL\Type\Definition\Type;

/**
 * Class InterfaceType
 */
abstract class InterfaceType
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
        ];
    }

    /**
     * Returns the schema object name
     *
     * @return string
     */
    abstract public static function getName(): string;

    /**
     * Returns the associated type generator class.
     *
     * @return string
     */
    abstract public static function getTypeGenerator(): string;
}
