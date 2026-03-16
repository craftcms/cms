<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use craft\base\Event as YiiEvent;
use craft\events\DefineInputOptionsEvent;
use CraftCms\Cms\Field\Events\DefineInputOptions;
use Illuminate\Support\Facades\Event;

/**
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\BaseOptionsField} instead.
 */
abstract class BaseOptionsField extends \CraftCms\Cms\Field\BaseOptionsField
{
    use \craft\base\LegacyEventConstants;

    /**
     * @event DefineInputOptionsEvent Event triggered when defining the options for the field's input.
     *
     * @since 4.4.0
     */
    public const string EVENT_DEFINE_OPTIONS = 'defineOptions';

    public static function registerEvents(): void
    {
        Event::listen(\CraftCms\Cms\Field\BaseOptionsField::componentEventName(\CraftCms\Cms\Field\BaseOptionsField::EVENT_DEFINE_OPTIONS), function(DefineInputOptions $event) {
            if (!YiiEvent::hasHandlers(self::class, self::EVENT_DEFINE_OPTIONS)) {
                return;
            }

            $yiiEvent = new DefineInputOptionsEvent([
                'options' => $event->options,
                'value' => $event->value,
                'element' => $event->element,
                'sender' => $event->field,
            ]);

            YiiEvent::trigger(self::class, self::EVENT_DEFINE_OPTIONS, $yiiEvent);

            $event->options = $yiiEvent->options;
        });
    }
}
