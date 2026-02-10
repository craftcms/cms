<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

use craft\base\Event as YiiEvent;
use craft\events\DefineFieldActionsEvent;
use CraftCms\Cms\FieldLayout\Events\DefineActionMenuItems;
use Illuminate\Support\Facades\Event;

/**
 * BaseField is the base class for native and custom fields that can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.5.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\FieldLayout\LayoutElements\BaseField} instead.
 */
abstract class BaseField extends \CraftCms\Cms\FieldLayout\LayoutElements\BaseField
{
    /**
     * @event DefineFieldActionsEvent The event that is triggered when defining action menu items.
     *
     * @see actionMenuItems()
     * @since 5.9.0
     */
    public const EVENT_DEFINE_ACTION_MENU_ITEMS = 'defineActionMenuItems';

    public static function registerEvents(): void
    {
        Event::listen(function(DefineActionMenuItems $event) {
            if (YiiEvent::hasHandlers(self::class, self::EVENT_DEFINE_ACTION_MENU_ITEMS)) {
                $yiiEvent = new DefineFieldActionsEvent([
                    'element' => $event->element,
                    'static' => $event->static,
                    'items' => $event->items,
                ]);

                YiiEvent::trigger(self::class, self::EVENT_DEFINE_ACTION_MENU_ITEMS, $yiiEvent);

                $event->items = $yiiEvent->items;
            }
        });
    }
}
