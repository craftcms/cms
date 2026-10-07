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
    /**
     * Mirrors a Craft 5 `registerXTypes` event onto a Craft 6 type registry.
     *
     * The event fires on the registry's first read, not here. Craft 5 fired
     * these from the getters, so handlers may assume request state — a user,
     * a site — that boot doesn't have.
     *
     * @param  class-string<Event>  $eventClass
     */
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

        $registry->defer(static function() use ($registry, $component, $eventName, $attribute, $eventClass) {
            $types = $registry->types();
            $event = new $eventClass([$attribute => $types->all()]);
            $component->trigger($eventName, $event);
            $transformedTypes = collect($event->{$attribute});

            $registry->remove(...$types->diff($transformedTypes));
            $registry->register(...$transformedTypes->diff($types));
        });
    }
}
