<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use GraphQL\Type\Definition\Type;

/**
 * SingularTypeInterface defines the common interface to be implemented by all types that have no similar types and,
 * thus, aren't generated.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface SingularTypeInterface
{
    /**
     * Return the name of the GraphQL type.
     *
     * @return string
     */
    public static function getName(): string;

    /**
     * Return an instance of the GraphQL type.
     *
     * @return Type
     */
    public static function getType(): Type;
}
