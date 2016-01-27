<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\base;

use yii\base\Arrayable;

/**
 * Component is the base class for classes representing Craft components in terms of objects.
 *
 * @property string $type The class name that should be used to represent the field
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class Component extends Model implements ComponentInterface, Arrayable
{
    // Static
    // =========================================================================

    /**
     * Returns the display name of this class.
     *
     * @return string The display name of this class.
     */
    public static function displayName()
    {
        $classNameParts = explode('\\', static::className());
        $displayName = array_pop($classNameParts);

        return $displayName;
    }

    /**
     * @inheritdoc
     */
    public static function classHandle()
    {
        $classNameParts = explode('\\', static::className());
        $handle = array_pop($classNameParts);

        return strtolower($handle);
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return static::className();
    }
}
