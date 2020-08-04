<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use craft\base\Element;
use craft\gql\GqlEntityRegistry;

/**
 * Class InterfaceType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
abstract class InterfaceType
{
    use GqlTypeTrait;

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
     * @since 3.5
     */
    public static function resolveElementTypeName(Element $element): string
    {
        return GqlEntityRegistry::prefixTypeName($element->getGqlTypeName());
    }
}
