<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

/**
 * Class GeneratorInterface
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
interface GeneratorInterface
{
    /**
     * Generate GraphQL types.
     *
     * @param mixed $context Context for generated types
     * @return ObjectType[]
     */
    public static function generateTypes($context = null): array;
}
