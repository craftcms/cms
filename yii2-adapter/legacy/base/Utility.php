<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * Utility is the base class for classes representing control panel utilities.
 *
 * Extends the Craft 6 class so that legacy utilities satisfy `UtilityTypes`'
 * contract. Craft 5 extended `Component`, but `UtilityInterface` is static
 * throughout and utilities are never instantiated, so nothing is lost.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. [[\CraftCms\Cms\Utility\Utility]] should be used instead.
 */
abstract class Utility extends \CraftCms\Cms\Utility\Utility implements UtilityInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        $classNameParts = explode('\\', static::class);
        return array_pop($classNameParts);
    }
}
