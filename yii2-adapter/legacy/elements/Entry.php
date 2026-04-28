<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use craft\base\ElementEventConstants;
use craft\base\Event as YiiEvent;
use craft\events\DefineEntryTypesEvent;
use craft\events\ElementCriteriaEvent;
use CraftCms\Cms\Entry\Events\DefineEntryTypes;
use CraftCms\Cms\Entry\Events\DefineMetaFields;
use CraftCms\Cms\Entry\Events\DefineParentSelectionCriteria;
use Illuminate\Support\Facades\Event;

/**
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Entry\Elements\Entry} instead.
 */
class Entry extends \CraftCms\Cms\Entry\Elements\Entry
{
    use ElementEventConstants;

    /**
     * @event DefineEntryTypesEvent The event that is triggered when defining the available entry types for the entry
     *
     * @see getAvailableEntryTypes()
     * @since 3.6.0
     */
    public const string EVENT_DEFINE_ENTRY_TYPES = 'defineEntryTypes';

    /**
     * @event ElementCriteriaEvent The event that is triggered when defining the parent selection criteria.
     *
     * @see _parentOptionCriteria()
     * @since 4.4.0
     */
    public const string EVENT_DEFINE_PARENT_SELECTION_CRITERIA = 'defineParentSelectionCriteria';

    /**
     * @event DefineMetaFields The event that is triggered when defining the meta fields.
     *
     * @see metaFieldsHtml()
     * @since 5.9.0
     */
    public const string EVENT_DEFINE_META_FIELDS = 'defineEntryMetaFields';

    public static function registerEvents(): void
    {
        Event::listen(function(DefineEntryTypes $event) {
            if (YiiEvent::hasHandlers(self::class, self::EVENT_DEFINE_ENTRY_TYPES)) {
                $yiiEvent = new DefineEntryTypesEvent([
                    'entryTypes' => $event->entryTypes,
                    'sender' => $event->entry,
                ]);

                YiiEvent::trigger(self::class, self::EVENT_DEFINE_ENTRY_TYPES, $yiiEvent);

                $event->entryTypes = $yiiEvent->entryTypes;
            }
        });

        Event::listen(function(DefineMetaFields $event) {
            if (YiiEvent::hasHandlers(self::class, self::EVENT_DEFINE_META_FIELDS)) {
                $yiiEvent = new \craft\events\DefineMetaFields([
                    'element' => $event->entry,
                    'sender' => $event->entry,
                    'static' => $event->static,
                    'fields' => $event->fields,
                ]);

                YiiEvent::trigger(self::class, self::EVENT_DEFINE_META_FIELDS, $yiiEvent);

                $event->fields = $yiiEvent->fields;
            }
        });

        Event::listen(function(DefineParentSelectionCriteria $event) {
            if (YiiEvent::hasHandlers(self::class, self::EVENT_DEFINE_PARENT_SELECTION_CRITERIA)) {
                $yiiEvent = new ElementCriteriaEvent([
                    'sender' => $event->entry,
                    'criteria' => $event->criteria,
                ]);

                YiiEvent::trigger(self::class, self::EVENT_DEFINE_PARENT_SELECTION_CRITERIA, $yiiEvent);

                $event->criteria = $yiiEvent->criteria;
            }
        });
    }
}
