<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Concerns;

use CraftCms\Cms\Component\Events\ComponentEvent;
use DateTimeInterface;
use Illuminate\Events\QueuedClosure;
use Illuminate\Support\Facades\Event;

trait SavableComponent
{
    use HasComponentEvents;

    public int|string|null $id = null;

    public ?DateTimeInterface $dateCreated = null;

    public ?DateTimeInterface $dateUpdated = null;

    /**
     * @event {@see ComponentEvent} The event triggered before the component is saved.
     *
     * You may set {@see ComponentEvent::$isValid} to `false` to prevent the component from getting saved.
     */
    public const string EVENT_BEFORE_SAVE = 'beforeSave';

    /**
     * @event {@see ComponentEvent} The event triggered after the component is saved.
     */
    public const string EVENT_AFTER_SAVE = 'afterSave';

    /**
     * @event {@see ComponentEvent} The event triggered before the component is deleted.
     *
     * You may set {@see ComponentEvent::$isValid} to `false` to prevent the component from getting deleted.
     */
    public const string EVENT_BEFORE_DELETE = 'beforeDelete';

    /**
     * @event {@see ComponentEvent} The event triggered before the delete is applied to the database.
     */
    public const string EVENT_BEFORE_APPLY_DELETE = 'beforeApplyDelete';

    /**
     * @event {@see ComponentEvent} The event triggered after the component is deleted.
     */
    public const string EVENT_AFTER_DELETE = 'afterDelete';

    public function getIsNew(): bool
    {
        return ! $this->id || (is_string($this->id) && str_starts_with($this->id, 'new'));
    }

    public static function onBeforeSave(QueuedClosure|callable|array|string $callback): void
    {
        static::listen(self::EVENT_BEFORE_SAVE, $callback);
    }

    public function beforeSave(bool $isNew): bool
    {
        event(
            self::componentEventName(self::EVENT_BEFORE_SAVE),
            $event = new ComponentEvent($this, $isNew),
        );

        return $event->isValid;
    }

    public static function onAfterSave(QueuedClosure|callable|array|string $callback): void
    {
        static::listen(self::EVENT_AFTER_SAVE, $callback);
    }

    public function afterSave(bool $isNew): void
    {
        event(self::componentEventName(self::EVENT_AFTER_SAVE), new ComponentEvent($this, $isNew));
    }

    public static function onBeforeDelete(QueuedClosure|callable|array|string $callback): void
    {
        static::listen(self::EVENT_BEFORE_DELETE, $callback);
    }

    public function beforeDelete(): bool
    {
        event(
            self::componentEventName(self::EVENT_BEFORE_DELETE),
            $event = new ComponentEvent($this),
        );

        return $event->isValid;
    }

    public static function onBeforeApplyDelete(QueuedClosure|callable|array|string $callback): void
    {
        static::listen(self::EVENT_BEFORE_APPLY_DELETE, $callback);
    }

    public function beforeApplyDelete(): void
    {
        event(self::componentEventName(self::EVENT_BEFORE_APPLY_DELETE), new ComponentEvent($this));
    }

    public static function onAfterDelete(QueuedClosure|callable|array|string $callback): void
    {
        static::listen(self::EVENT_AFTER_DELETE, $callback);
    }

    public function afterDelete(): void
    {
        event(self::componentEventName(self::EVENT_AFTER_DELETE), new ComponentEvent($this));
    }
}
