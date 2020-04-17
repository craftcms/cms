<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

/**
 * Class Mutation
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
abstract class Mutation
{
    use GqlTypeTrait;

    /**
     * Returns the mutations defined by the class as an array.
     *
     * @return array
     */
    abstract public static function getMutations(): array;
}
