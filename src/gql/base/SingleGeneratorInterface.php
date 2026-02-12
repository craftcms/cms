<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

/**
 * SingleGeneratorInterface defines the common interface to be implemented by generator classes that support generating
 * a single GraphQL type at a time.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
interface SingleGeneratorInterface
{
    /**
     * Generate a single GraphQL type based on a context.
     *
     * @param mixed $context Context for generated types
     * @return mixed
     * @since 3.5.0
     */
    public static function generateType(mixed $context): mixed;
}
