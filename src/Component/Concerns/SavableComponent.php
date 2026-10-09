<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Concerns;

use CraftCms\Cms\Component\Events\ComponentDeleteApplying;
use CraftCms\Cms\Component\Events\ComponentDeleted;
use CraftCms\Cms\Component\Events\ComponentDeleting;
use CraftCms\Cms\Component\Events\ComponentSaved;
use CraftCms\Cms\Component\Events\ComponentSaving;
use DateTimeInterface;
use Illuminate\Events\QueuedClosure;
use Illuminate\Support\Facades\Event;

trait SavableComponent
{
    public int|string|null $id = null;

    public ?DateTimeInterface $dateCreated = null;

    public ?DateTimeInterface $dateUpdated = null;

    public function getIsNew(): bool
    {
        return ! $this->id || (is_string($this->id) && str_starts_with($this->id, 'new'));
    }

    /** @param callable|array{class-string|object, string}|string $callback */
    public static function onBeforeSave(QueuedClosure|callable|array|string $callback): void
    {
        self::listenForComponentEvent(ComponentSaving::class, $callback);
    }

    public function beforeSave(bool $isNew): bool
    {
        event($event = new ComponentSaving($this, $isNew));

        return $event->isValid;
    }

    /** @param callable|array{class-string|object, string}|string $callback */
    public static function onAfterSave(QueuedClosure|callable|array|string $callback): void
    {
        self::listenForComponentEvent(ComponentSaved::class, $callback);
    }

    public function afterSave(bool $isNew): void
    {
        event(new ComponentSaved($this, $isNew));
    }

    /** @param callable|array{class-string|object, string}|string $callback */
    public static function onBeforeDelete(QueuedClosure|callable|array|string $callback): void
    {
        self::listenForComponentEvent(ComponentDeleting::class, $callback);
    }

    public function beforeDelete(): bool
    {
        event($event = new ComponentDeleting($this));

        return $event->isValid;
    }

    /** @param callable|array{class-string|object, string}|string $callback */
    public static function onBeforeApplyDelete(QueuedClosure|callable|array|string $callback): void
    {
        self::listenForComponentEvent(ComponentDeleteApplying::class, $callback);
    }

    public function beforeApplyDelete(): void
    {
        event(new ComponentDeleteApplying($this));
    }

    /** @param callable|array{class-string|object, string}|string $callback */
    public static function onAfterDelete(QueuedClosure|callable|array|string $callback): void
    {
        self::listenForComponentEvent(ComponentDeleted::class, $callback);
    }

    public function afterDelete(): void
    {
        event(new ComponentDeleted($this));
    }

    /**
     * @param  class-string  $event
     * @param  callable|array{class-string|object, string}|string  $callback
     */
    private static function listenForComponentEvent(string $event, QueuedClosure|callable|array|string $callback): void
    {
        $class = static::class;
        $listener = Event::getFacadeRoot()->makeListener(
            $callback instanceof QueuedClosure ? $callback->resolve() : $callback,
        );

        Event::listen($event, function ($event) use ($class, $listener): void {
            if ($event->component instanceof $class) {
                $listener($event::class, [$event]);
            }
        });
    }
}
