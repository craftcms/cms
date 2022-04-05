<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use craft\base\Element;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\Type;

/**
 * Class InterfaceType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
abstract class InterfaceType
{
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

    /**
     * Resolve an element type name.
     *
     * @param Element $element
     * @return string
     * @since 3.5.0
     */
    public static function resolveElementTypeName(Element $element): string
    {
        return GqlEntityRegistry::prefixTypeName($element->getGqlTypeName());
    }

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
