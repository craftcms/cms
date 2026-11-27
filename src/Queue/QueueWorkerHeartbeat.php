<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue;

use Carbon\CarbonInterface;
use CraftCms\Cms\Cms;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;

/**
 * Tracks whether long-running queue workers are processing Craft’s queues.
 *
 * @internal
 *
 * @since 6.0.0
 */
#[Singleton]
class QueueWorkerHeartbeat
{
    private const string CACHE_KEY_PREFIX = 'craft:queue-worker-heartbeat:';

    /**
     * How long after its last loop a worker is considered stopped, in seconds.
     */
    private const int TIMEOUT = 180;

    /**
     * How often a worker refreshes the heartbeat, in seconds.
     */
    private const int INTERVAL = 60;

    /**
     * When this process last recorded each queue’s heartbeat, as Unix timestamps indexed by queue name.
     *
     * @var array<string, int>
     */
    private array $recordedAt = [];

    /**
     * Records that a worker is polling the given queues.
     *
     * Only workers on the default queue connection, which Craft pushes its jobs to, are recorded. A worker loops
     * after every job and every few seconds while idle, so each queue is only written to the cache once a minute.
     *
     * @param  string  $connection  The worker’s queue connection.
     * @param  string  $queues  The worker’s comma-separated queue names.
     *
     * @internal Only Craft’s `Illuminate\Queue\Events\Looping` listener should call this.
     */
    public function record(string $connection, string $queues): void
    {
        if ($connection !== config('queue.default')) {
            return;
        }

        $now = Date::now()->getTimestamp();

        foreach (explode(',', $queues) as $queue) {
            if (isset($this->recordedAt[$queue]) && $now - $this->recordedAt[$queue] < self::INTERVAL) {
                continue;
            }

            Cache::forever(self::CACHE_KEY_PREFIX.$queue, $now);
            $this->recordedAt[$queue] = $now;
        }
    }

    /**
     * Returns when a `queue:work` worker, other than one run with `--once`, last polled both the `queueName` and
     * `lowPriorityQueueName` queues on the default connection: the earlier of the two queues’ last polls, or `null`
     * if either hasn’t been polled since the cache was last cleared.
     */
    public function lastRanAt(): ?CarbonInterface
    {
        $timestamps = collect([Cms::config()->queueName, Cms::config()->lowPriorityQueueName])
            ->unique()
            ->map(fn (string $queue) => Cache::get(self::CACHE_KEY_PREFIX.$queue));

        if ($timestamps->contains(fn (mixed $timestamp) => ! is_int($timestamp))) {
            return null;
        }

        return Date::createFromTimestamp($timestamps->min(), date_default_timezone_get());
    }

    /**
     * Returns whether a worker has polled both of Craft’s queues in the last three minutes.
     *
     * A worker only polls between jobs, so this turns `false` while one spends more than three minutes on a job. The
     * browser queue runner still stays off then, because it skips while any job is reserved. It needs a cache store
     * shared with the server that runs the workers, so treat `false` as a reason to fall back, not as an error.
     */
    public function isRunning(): bool
    {
        $lastRanAt = $this->lastRanAt();

        return $lastRanAt !== null && $lastRanAt->getTimestamp() > Date::now()->getTimestamp() - self::TIMEOUT;
    }
}
