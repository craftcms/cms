<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \CraftCms\Cms\Activity\Models\ActivityEvent record(\CraftCms\Cms\Activity\Contracts\ActivityEventTypeInterface $event)
 * @method static \Illuminate\Database\Eloquent\Builder query()
 * @method static \Illuminate\Contracts\Support\Htmlable|string format(\CraftCms\Cms\Activity\Models\ActivityEvent $event)
 * @method static string|null icon(\CraftCms\Cms\Activity\Models\ActivityEvent $event)
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
