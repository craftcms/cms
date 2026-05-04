<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use craft\base\Event as YiiEvent;
use craft\events\DefineEntryTypesForFieldEvent;
use CraftCms\Cms\Field\Events\DefineEntryTypesForField;
use Illuminate\Support\Facades\Event;

/**
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Matrix} instead.
 */
class Matrix extends \CraftCms\Cms\Field\Matrix
{
    use \craft\base\FieldEventConstants;
    use \craft\base\LegacyEventConstants;

    /**
     * @event DefineEntryTypesForFieldEvent The event that is triggered when defining the available entry types.
     *
     * @since 5.0.0
     */
    public const string EVENT_DEFINE_ENTRY_TYPES = 'defineEntryTypes';

    public static function registerEvents(): void
    {
        Event::listen(function(DefineEntryTypesForField $event) {
            if (!$event->field instanceof \CraftCms\Cms\Field\Matrix) {
                return;
            }

            if (!YiiEvent::hasHandlers(self::class, self::EVENT_DEFINE_ENTRY_TYPES)) {
                return;
            }

            $yiiEvent = new DefineEntryTypesForFieldEvent([
                'entryTypes' => $event->entryTypes,
                'element' => $event->element,
                'value' => $event->value,
                'sender' => $event->field,
            ]);

            YiiEvent::trigger(self::class, self::EVENT_DEFINE_ENTRY_TYPES, $yiiEvent);

            $event->entryTypes = $yiiEvent->entryTypes;
        });
    }
}
