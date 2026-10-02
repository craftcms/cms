<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Event;

use craft\events\RegisterComponentTypesEvent;
use CraftCms\Cms\Component\TypeRegistry;
use yii\base\Component;
use yii\base\Event;

/** @internal */
class TypeRegistryCompatibility
{
    /** @param class-string<Event> $eventClass */
    public static function reconcile(
        TypeRegistry $registry,
        Component $component,
        string $eventName,
        string $attribute = 'types',
        string $eventClass = RegisterComponentTypesEvent::class,
    ): void {
        if (!$component->hasEventHandlers($eventName)) {
            return;
        }

        $types = $registry->types();
        $event = new $eventClass([$attribute => $types->all()]);
        $component->trigger($eventName, $event);
        $transformedTypes = collect($event->{$attribute});

        $registry->remove(...$types->diff($transformedTypes));
        $registry->register(...$transformedTypes->diff($types));
    }
}
