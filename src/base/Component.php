<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\events\DefineBehaviorsEvent;

/**
 * Component is the base class for classes representing Craft components in terms of objects.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class Component extends Model implements ComponentInterface
{
    // Constants
    // =========================================================================

    /**
     * @event \yii\base\Event The event that is triggered after the component's init cycle
     * @see init()
     */
    const EVENT_INIT = 'init';

    /**
     * @event DefineBehaviorsEvent The event that is triggered when defining the class behaviors
     * @see behaviors()
     */
    const EVENT_DEFINE_BEHAVIORS = 'defineBehaviors';

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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->hasEventHandlers(self::EVENT_INIT)) {
            $this->trigger(self::EVENT_INIT);
        }
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        // Fire a 'defineBehaviors' event
        $event = new DefineBehaviorsEvent();
        $this->trigger(self::EVENT_DEFINE_BEHAVIORS, $event);
        return $event->behaviors;
    }
}
