<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use craft\events\DefineBehaviorsEvent;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Twig\Variables\CraftVariable as CoreCraftVariable;
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
    public const string EVENT_DEFINE_BEHAVIORS = 'defineBehaviors';

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

        if ($this->hasMethod($name)) {
            return parent::__call($name, $params);
        }

        return app(CoreCraftVariable::class)->$name(...$params);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if (!$this->has($name) && !$this->canGetProperty($name) && !$this->hasMethod($name)) {
            $craftVariable = app(CoreCraftVariable::class);

            if (property_exists($craftVariable, $name)) {
                return $craftVariable->$name;
            }

            if (CoreCraftVariable::hasMacro($name)) {
                return $craftVariable->$name();
            }
        }

        return parent::__get($name);
    }

    /**
     * @inheritdoc
     */
    public function __isset($name)
    {
        if ($this->has($name) || $this->canGetProperty($name) || $this->hasMethod($name)) {
            return parent::__isset($name);
        }

        $craftVariable = app(CoreCraftVariable::class);

        if (property_exists($craftVariable, $name)) {
            return isset($craftVariable->$name);
        }

        return CoreCraftVariable::hasMacro($name) && $craftVariable->$name() !== null;
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
