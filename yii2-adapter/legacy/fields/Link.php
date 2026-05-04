<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use craft\base\Event as YiiEvent;
use craft\events\RegisterComponentTypesEvent;
use CraftCms\Cms\Field\Events\RegisterLinkTypes;
use Illuminate\Support\Facades\Event;

/**
 * @since 5.3.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Link} instead.
 */
class Link extends \CraftCms\Cms\Field\Link
{
    use \craft\base\FieldEventConstants;
    use \craft\base\LegacyEventConstants;

    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering the link types for Link fields.
     *
     * @see types()
     */
    public const string EVENT_REGISTER_LINK_TYPES = 'registerLinkTypes';

    public static function registerEvents(): void
    {
        Event::listen(function(RegisterLinkTypes $event) {
            if (!YiiEvent::hasHandlers(self::class, self::EVENT_REGISTER_LINK_TYPES)) {
                return;
            }

            $yiiEvent = new RegisterComponentTypesEvent([
                'types' => $event->types,
            ]);

            YiiEvent::trigger(self::class, self::EVENT_REGISTER_LINK_TYPES, $yiiEvent);

            $event->types = $yiiEvent->types;
        });
    }
}
