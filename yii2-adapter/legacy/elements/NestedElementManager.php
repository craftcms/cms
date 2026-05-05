<?php

namespace craft\elements;

use craft\base\Event as YiiEvent;
use craft\events\BulkElementsEvent;
use craft\events\DuplicateNestedElementsEvent;
use CraftCms\Cms\Element\Events\NestedElementRevisionsCreated;
use CraftCms\Cms\Element\Events\NestedElementsDuplicated as NewDuplicateNestedElementsEvent;
use CraftCms\Cms\Element\Events\NestedElementsSaved;
use Illuminate\Support\Facades\Event;

/**
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\NestedElementManager} instead.
 */
class NestedElementManager extends \CraftCms\Cms\Element\NestedElementManager
{
    use \craft\base\LegacyEventConstants;

    public const EVENT_AFTER_SAVE_ELEMENTS = 'afterSaveElements';

    public const EVENT_AFTER_DUPLICATE_NESTED_ELEMENTS = 'afterDuplicateNestedElements';

    public const EVENT_AFTER_CREATE_REVISIONS = 'afterCreateRevisions';

    public static function registerEvents(): void
    {
        Event::listen(function(NestedElementsSaved $event) {
            if (!YiiEvent::hasHandlers(self::class, self::EVENT_AFTER_SAVE_ELEMENTS)) {
                return;
            }

            YiiEvent::trigger(self::class, self::EVENT_AFTER_SAVE_ELEMENTS, new BulkElementsEvent([
                'elements' => $event->elements,
                'sender' => $event->manager,
            ]));
        });

        Event::listen(function(NewDuplicateNestedElementsEvent $event) {
            if (!YiiEvent::hasHandlers(self::class, self::EVENT_AFTER_DUPLICATE_NESTED_ELEMENTS)) {
                return;
            }

            YiiEvent::trigger(self::class, self::EVENT_AFTER_DUPLICATE_NESTED_ELEMENTS, new DuplicateNestedElementsEvent([
                'source' => $event->source,
                'target' => $event->target,
                'newElementIds' => $event->newElementIds,
                'sender' => $event->manager,
            ]));
        });

        Event::listen(function(NestedElementRevisionsCreated $event) {
            if (!YiiEvent::hasHandlers(self::class, self::EVENT_AFTER_CREATE_REVISIONS)) {
                return;
            }

            YiiEvent::trigger(self::class, self::EVENT_AFTER_CREATE_REVISIONS, new DuplicateNestedElementsEvent([
                'source' => $event->source,
                'target' => $event->target,
                'newElementIds' => $event->newElementIds,
                'sender' => $event->manager,
            ]));
        });
    }
}
