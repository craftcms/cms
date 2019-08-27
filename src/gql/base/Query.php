<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

/**
 * Class Query
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
abstract class Query
{
    // Traits
    // =========================================================================
    use GqlTypeTrait;

    // Methods
    // =========================================================================
    /**
     * Return the queries defined by the class as an array
     *
     * @param bool $checkToken Whether the token should be checked for allowed queries. Defaults to `true`.
     * @return array
     */
    abstract public static function getQueries($checkToken = true): array;
}
