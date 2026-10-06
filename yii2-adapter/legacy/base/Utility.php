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
 * Craft 5 had this extend `Component`, which made every utility a model. Nothing
 * ever used that — `UtilityInterface` is static from top to bottom and utilities
 * are never instantiated — whereas extending the Craft 6 class is what lets a
 * plugin's utility survive `UtilityTypes`' contract check and show up in the
 * control panel without being ported.
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
