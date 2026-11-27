<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isRunning()
 *
 * @see \CraftCms\Cms\Console\SchedulerHeartbeat
 * @since 6.0.0
 */
class SchedulerHeartbeat extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Console\SchedulerHeartbeat::class;
    }
}
