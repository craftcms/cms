<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\base;

/**
 * ComponentInterface defines the common interface to be implemented by Craft component classes.
 *
 * A class implementing this interface should also implement [[\yii\base\Arrayable]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
interface ComponentInterface
{
    // Static
    // =========================================================================

    /**
     * Returns the display name of this class.
     *
     * @return string The display name of this class.
     */
    public static function displayName(): string;

    /**
     * Returns a unique handle that can be used to refer to this class.
     *
     * @return string The class handle.
     */
    public static function classHandle(): string;
}
