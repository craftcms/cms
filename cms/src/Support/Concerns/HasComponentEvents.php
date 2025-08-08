<?php

namespace CraftCms\Cms\Support\Concerns;

use Illuminate\Events\QueuedClosure;
use Illuminate\Support\Facades\Event;

/**
 * @since 6.0.0
 */
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

    protected static function componentEventName(string $event): string
    {
        return "component.{$event}: ".static::class;
    }
}
