<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Activity\Contracts\ActivityEventTypeInterface;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ActivityEvent record(ActivityEventTypeInterface $event)
 * @method static Builder<ActivityEvent> query()
 * @method static string|Htmlable format(ActivityEvent $event)
 * @method static string icon(ActivityEvent $event)
 *
 * @see \CraftCms\Cms\Activity\Activities
 */
class Activities extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Activity\Activities::class;
    }
}
