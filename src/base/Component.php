<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\base;

/**
 * Component is the base class for classes representing Craft components in terms of objects.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class Component extends Model implements ComponentInterface
{
    // Constants
    // =========================================================================

    /**
     * @event ComponentEvent The event that is triggered after the component's init cycle
     *
     * This is a good place to register custom behaviors on the component
     */
    const EVENT_AFTER_INIT = 'afterInit';
    
    // Static
    // =========================================================================

    /**
     * Returns the display name of this class.
     *
     * @return string The display name of this class.
     */
    public static function displayName(): string
    {
        $classNameParts = explode('\\', static::class);

        return array_pop($classNameParts);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->trigger(self::EVENT_AFTER_INIT, new \yii\base\Event);
    }
}
