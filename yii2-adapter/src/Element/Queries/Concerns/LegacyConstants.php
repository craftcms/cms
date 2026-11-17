<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Element\Queries\Concerns;

use craft\events\CancelableEvent;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\Events\ElementQueryPrepared;
use CraftCms\Cms\Element\Queries\Events\ElementQueryPreparing;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;
use CraftCms\Yii2Adapter\Event\LegacyClassEvents;
use Illuminate\Support\Facades\Event;

/**
 * @internal
 * @deprecated 6.0.0
 * @phpstan-ignore trait.unused
 */
trait LegacyConstants
{
    /**
     * @event CancelableEvent An event that is triggered at the beginning of preparing an element query for the query builder.
     * @deprecated 6.0.0 Use {@see ElementQueryPreparing} instead.
     */
    public const string EVENT_BEFORE_PREPARE = 'beforePrepare';

    /**
     * @event CancelableEvent An event that is triggered at the end of preparing an element query for the query builder.
     * @deprecated 6.0.0 Use {@see ElementQueryPrepared} instead.
     */
    public const string EVENT_AFTER_PREPARE = 'afterPrepare';

    public static function registerEvents(): void
    {
        Event::listen(fn(ElementQueryPreparing $event) => self::triggerPrepareEvent($event->query, self::EVENT_BEFORE_PREPARE));
        Event::listen(fn(ElementQueryPrepared $event) => self::triggerPrepareEvent($event->query, self::EVENT_AFTER_PREPARE));
    }

    /**
     * @param ElementQuery<*> $query
     * @throws QueryAbortedException if a handler cancels the event
     */
    private static function triggerPrepareEvent(ElementQuery $query, string $name): void
    {
        if (!LegacyClassEvents::hasHandlers($query::class, $name)) {
            return;
        }

        $yiiEvent = new CancelableEvent(['sender' => $query]);

        LegacyClassEvents::trigger($query::class, $name, $yiiEvent);

        if (!$yiiEvent->isValid) {
            throw new QueryAbortedException();
        }
    }
}
