<?php

namespace CraftCms\Cms\Component\Concerns;

use Illuminate\Events\QueuedClosure;
use Illuminate\Support\Facades\Event;

trait HasComponentEvents
{
    /**
     * Register a component event with the dispatcher.
     *
     * @param  QueuedClosure|callable|array|class-string  $callback
     */
    protected static function registerModelEvent(string $event, QueuedClosure|callable|array|string $callback): void
    {
        Event::listen(self::componentEventName($event), $callback);
    }

    public static function componentEventName(string $event): string
    {
        return "component.{$event}: ".static::class;
    }
}
