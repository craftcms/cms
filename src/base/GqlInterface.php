<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * GqlInterface defines the common interface to be implemented by classes that support GraphQL.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
interface GqlInterface
{
    // Public Methods
    // =========================================================================

    /**
     * Returns an array of the model's GraphQL type definitions
     * @return Type[]
     */
    public static function getGqlTypeDefinition(): array;

    /**
     * Returns the model's GraphQL type name
     * @return string
     */
    public static function getGqlTypeName(): string;

    /**
     * Return all the queries defined by the model.
     * @return ObjectType[]
     */
    public static function getGqlQueryDefinitions(): array;
}
