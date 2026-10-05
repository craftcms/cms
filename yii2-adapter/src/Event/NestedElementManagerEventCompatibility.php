<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Event;

use craft\base\Event as YiiEvent;
use craft\elements\NestedElementManager;
use craft\events\BulkElementsEvent;
use craft\events\DuplicateNestedElementsEvent;
use CraftCms\Cms\Element\Events\NestedElementRevisionsCreated;
use CraftCms\Cms\Element\Events\NestedElementsDuplicated as NewDuplicateNestedElementsEvent;
use CraftCms\Cms\Element\Events\NestedElementsSaved;
use Illuminate\Support\Facades\Event;

readonly class NestedElementManagerEventCompatibility
{
    public function boot(): void
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
