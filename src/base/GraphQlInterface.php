<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use GraphQL\Type\Definition\ObjectType;

/**
 * GraphQlInterface defines the common interface to be implemented by classes that support GraphQL.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
interface GraphQlInterface
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the model's GraphQL type definition
     * @return ObjectType
     */
    public static function getGraphQlTypeDefinition(): ObjectType;

    /**
     * Returns the model's GraphQL type name
     * @return ObjectType
     */
    public static function getGraphQlTypeName(): string;

    /**
     * Return all the queries defined by the model.
     * @return array
     */
    public static function getGraphQlQueryDefinitions(): array;
}
