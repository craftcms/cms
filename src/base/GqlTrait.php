<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * GqlTrait implements the common methods and properties for classes that support GraphQL.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
trait GqlTrait
{
    // Public Methods
    // =========================================================================

    /**
     * Return a list of typeName => TypeClassName.
     *
     * @return array
     */
    public static function getGqlTypeList(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getGqlTypeDefinitions(): array
    {
        $typeList = [];

        /**
         * @var string $name type name
         * @var string $type tpye class
         */
        foreach (self::getGqlTypeList() as $name => $type) {
            $typeList[$name] = $type::getType();
        }

        return $typeList;
    }

    /**
     * @inheritdoc
     */
    public static function getGqlQueryDefinitions(): array
    {
        return [];
    }
}
