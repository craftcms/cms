<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

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
}
