<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console;

use Illuminate\Support\Facades\Cache;

/**
 * Tracks whether Laravel’s scheduler is running.
 *
 * @since 6.0.0
 */
class SchedulerHeartbeat
{
    private const string CACHE_KEY = 'craft:scheduler-heartbeat';

    /**
     * How long after its last run the scheduler is considered stopped, in seconds.
     */
    private const int TIMEOUT = 180;

    /**
     * Records that the scheduler ran just now.
     *
     * @internal Only Craft’s `craft:scheduler-heartbeat` scheduled task should call this.
     */
    public function record(): void
    {
        Cache::put(self::CACHE_KEY, true, self::TIMEOUT);
    }

    /**
     * Returns whether something has run `schedule:run` in the last three minutes.
     *
     * It stays `true` while the scheduler is paused or the system is offline. It needs a cache store
     * shared with the server that runs `schedule:run`, and returns `false` for up to a minute after
     * the cache is cleared, so treat `false` as a reason to fall back, not as an error.
     */
    public function isRunning(): bool
    {
        return Cache::has(self::CACHE_KEY);
    }
}
