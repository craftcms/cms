<?php

declare(strict_types=1);
namespace CraftCms\Cms\Element\Concerns;

use craft\base\Event as YiiEvent;
use craft\elements\NestedElementManager;
use craft\events\BulkElementsEvent;
use craft\events\DuplicateNestedElementsEvent;
use CraftCms\Cms\Element\Events\NestedElementRevisionsCreated;
use CraftCms\Cms\Element\Events\NestedElementsDuplicated as NewDuplicateNestedElementsEvent;
use CraftCms\Cms\Element\Events\NestedElementsSaved;
use Illuminate\Support\Facades\Event;

/**
 * @internal
 * @deprecated 6.0.0
 * @phpstan-ignore trait.unused
 */
trait LegacyNestedElementManager
{
    public const string EVENT_DEFINE_BEHAVIORS = 'defineBehaviors';

    public const EVENT_AFTER_SAVE_ELEMENTS = 'afterSaveElements';

    public const EVENT_AFTER_DUPLICATE_NESTED_ELEMENTS = 'afterDuplicateNestedElements';

    public const EVENT_AFTER_CREATE_REVISIONS = 'afterCreateRevisions';

    public static function registerEvents(): void
    {
        Event::listen(function(NestedElementsSaved $event) {
            if (!YiiEvent::hasHandlers(NestedElementManager::class, NestedElementManager::EVENT_AFTER_SAVE_ELEMENTS)) {
                return;
            }

            YiiEvent::trigger(NestedElementManager::class, NestedElementManager::EVENT_AFTER_SAVE_ELEMENTS, new BulkElementsEvent([
                'elements' => $event->elements,
                'sender' => $event->manager,
            ]));
        });

        Event::listen(function(NewDuplicateNestedElementsEvent $event) {
            if (!YiiEvent::hasHandlers(NestedElementManager::class, NestedElementManager::EVENT_AFTER_DUPLICATE_NESTED_ELEMENTS)) {
                return;
            }

            YiiEvent::trigger(NestedElementManager::class, NestedElementManager::EVENT_AFTER_DUPLICATE_NESTED_ELEMENTS, new DuplicateNestedElementsEvent([
                'source' => $event->source,
                'target' => $event->target,
                'newElementIds' => $event->newElementIds,
                'sender' => $event->manager,
            ]));
        });

        Event::listen(function(NestedElementRevisionsCreated $event) {
            if (!YiiEvent::hasHandlers(NestedElementManager::class, NestedElementManager::EVENT_AFTER_CREATE_REVISIONS)) {
                return;
            }

            YiiEvent::trigger(NestedElementManager::class, NestedElementManager::EVENT_AFTER_CREATE_REVISIONS, new DuplicateNestedElementsEvent([
                'source' => $event->source,
                'target' => $event->target,
                'newElementIds' => $event->newElementIds,
                'sender' => $event->manager,
            ]));
        });
    }
}
