<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Concerns;

use Illuminate\Events\QueuedClosure;
use Illuminate\Support\Facades\Event;

trait HasComponentEvents
{
    /**
     * Listen for a component event.
     *
     * @param  QueuedClosure|callable|array|class-string  $callback
     */
    public static function listen(string $event, QueuedClosure|callable|array|string $callback): void
    {
        foreach (static::getClasses() as $class) {
            Event::listen(self::componentEventName($event, $class), $callback);
        }
    }

    public function hasComponentListeners(string $event): bool
    {
        return array_any(
            static::getClasses(),
            fn ($class) => Event::hasListeners(self::componentEventName($event, $class)),
        );
    }

    public static function componentEventName(string $event, ?string $class = null): string
    {
        return "component.{$event}: ".($class ?? static::class);
    }

    public function dispatchComponentEvent(string $event, mixed $payload): void
    {
        foreach (static::getClasses() as $class) {
            Event::dispatch(self::componentEventName($event, $class), $payload);

            if (property_exists($payload, 'handled') && $payload->handled) {
                return;
            }
        }
    }

    protected static function getClasses(): array
    {
        $class = static::class;

        return array_merge(
            [$class],
            class_parents($class),
            class_implements($class)
        );
    }
}
