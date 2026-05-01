<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Concerns;

use CraftCms\Cms\Component\Events\ApplyingComponentDelete;
use CraftCms\Cms\Component\Events\ComponentDeleted;
use CraftCms\Cms\Component\Events\ComponentSaved;
use CraftCms\Cms\Component\Events\DeletingComponent;
use CraftCms\Cms\Component\Events\SavingComponent;
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

    public static function onBeforeSave(QueuedClosure|callable|array|string $callback): void
    {
        self::listenForComponentEvent(SavingComponent::class, $callback);
    }

    public function beforeSave(bool $isNew): bool
    {
        event($event = new SavingComponent($this, $isNew));

        return $event->isValid;
    }

    public static function onAfterSave(QueuedClosure|callable|array|string $callback): void
    {
        self::listenForComponentEvent(ComponentSaved::class, $callback);
    }

    public function afterSave(bool $isNew): void
    {
        event(new ComponentSaved($this, $isNew));
    }

    public static function onBeforeDelete(QueuedClosure|callable|array|string $callback): void
    {
        self::listenForComponentEvent(DeletingComponent::class, $callback);
    }

    public function beforeDelete(): bool
    {
        event($event = new DeletingComponent($this));

        return $event->isValid;
    }

    public static function onBeforeApplyDelete(QueuedClosure|callable|array|string $callback): void
    {
        self::listenForComponentEvent(ApplyingComponentDelete::class, $callback);
    }

    public function beforeApplyDelete(): void
    {
        event(new ApplyingComponentDelete($this));
    }

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
