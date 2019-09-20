<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\ObjectType as GqlObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Trait GqlTypeTrait
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
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

    /**
     * Return conditional fields for this type.
     *
     * @return array
     */
    protected static function getConditionalFields(): array
    {
        return [];
    }
}
