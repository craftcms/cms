<?php
declare(strict_types=1);
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
 * GqlTypeInterface defines the common interface to be implemented by all GraphQL type classes provided by Craft and plugins.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface NonGeneratedTypeInterface
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
