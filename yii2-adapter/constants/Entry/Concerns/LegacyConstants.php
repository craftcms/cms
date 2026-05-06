<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Concerns;

use craft\base\ElementEventConstants;
use craft\base\Event as YiiEvent;
use craft\elements\Entry;
use craft\events\DefineEntryTypesEvent;
use craft\events\DefineMetaFields;
use craft\events\ElementCriteriaEvent;
use CraftCms\Cms\Entry\Events\EntryMetaFieldsResolving;
use CraftCms\Cms\Entry\Events\EntryParentSelectionCriteriaResolving;
use CraftCms\Cms\Entry\Events\EntryTypesResolving;
use Deprecated;
use Illuminate\Support\Facades\Event;

/**
 * @internal
 * @deprecated 6.0.0
 * @phpstan-ignore trait.unused
 */
trait LegacyConstants
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
        Event::listen(function(EntryTypesResolving $event) {
            if (YiiEvent::hasHandlers(Entry::class, Entry::EVENT_DEFINE_ENTRY_TYPES)) {
                $yiiEvent = new DefineEntryTypesEvent([
                    'entryTypes' => $event->entryTypes,
                    'sender' => $event->entry,
                ]);

                YiiEvent::trigger(Entry::class, Entry::EVENT_DEFINE_ENTRY_TYPES, $yiiEvent);

                $event->entryTypes = $yiiEvent->entryTypes;
            }
        });

        Event::listen(function(EntryMetaFieldsResolving $event) {
            if (YiiEvent::hasHandlers(Entry::class, Entry::EVENT_DEFINE_META_FIELDS)) {
                $yiiEvent = new DefineMetaFields([
                    'element' => $event->entry,
                    'sender' => $event->entry,
                    'static' => $event->static,
                    'fields' => $event->fields,
                ]);

                YiiEvent::trigger(Entry::class, Entry::EVENT_DEFINE_META_FIELDS, $yiiEvent);

                $event->fields = $yiiEvent->fields;
            }
        });

        Event::listen(function(EntryParentSelectionCriteriaResolving $event) {
            if (YiiEvent::hasHandlers(Entry::class, Entry::EVENT_DEFINE_PARENT_SELECTION_CRITERIA)) {
                $yiiEvent = new ElementCriteriaEvent([
                    'sender' => $event->entry,
                    'criteria' => $event->criteria,
                ]);

                YiiEvent::trigger(Entry::class, Entry::EVENT_DEFINE_PARENT_SELECTION_CRITERIA, $yiiEvent);

                $event->criteria = $yiiEvent->criteria;
            }
        });
    }
}
