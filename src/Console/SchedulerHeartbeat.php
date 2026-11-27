<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;

/**
 * Tracks whether Laravel’s scheduler is running.
 *
 * @internal
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
        Cache::forever(self::CACHE_KEY, Date::now()->getTimestamp());
    }

    /**
     * Returns when something last ran `schedule:run`, or `null` if nothing has since the cache was last cleared.
     */
    public function lastRanAt(): ?CarbonInterface
    {
        $timestamp = Cache::get(self::CACHE_KEY);

        return is_int($timestamp) ? Date::createFromTimestamp($timestamp, date_default_timezone_get()) : null;
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
        $lastRanAt = $this->lastRanAt();

        return $lastRanAt !== null && $lastRanAt->getTimestamp() > Date::now()->getTimestamp() - self::TIMEOUT;
    }
}
