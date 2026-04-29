<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use BadMethodCallException;
use craft\base\LegacyEventConstants;
use craft\events\DefineBehaviorsEvent;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Twig\Variables\Io;
use yii\di\ServiceLocator;

/**
 * Craft defines the `craft` global template variable.
 *
 * @property Cp $cp
 * @property Io $io
 * @property OAuth $oauth
 * @property Rebrand $rebrand
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Twig\Variables\CraftVariable} instead.
 */
class CraftVariable extends ServiceLocator
{
    use LegacyEventConstants;

    /**
     * @event \yii\base\Event The event that is triggered after the component's init cycle
     * @see init()
     */
    public const EVENT_INIT = 'init';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        if ($this->hasEventHandlers(self::EVENT_INIT)) {
            $this->trigger(self::EVENT_INIT);
        }
    }

    /**
     * @inheritdoc
     */
    public function __call($name, $params)
    {
        if (method_exists(app(), $name)) {
            return app()->$name(...$params);
        }

        throw new BadMethodCallException("Method $name does not exist on CraftVariable.");
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        // Fire a 'defineBehaviors' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_BEHAVIORS)) {
            $event = new DefineBehaviorsEvent();
            $this->trigger(self::EVENT_DEFINE_BEHAVIORS, $event);
            return $event->behaviors;
        }

        return [];
    }
}
