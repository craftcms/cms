<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue;

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

            Cache::put(self::CACHE_KEY_PREFIX.$queue, true, self::TIMEOUT);
            $this->recordedAt[$queue] = $now;
        }
    }

    /**
     * Returns whether `queue:work` workers, other than ones run with `--once`, have polled both the `queueName` and
     * `lowPriorityQueueName` queues on the default connection in the last three minutes.
     *
     * It turns `false` while a worker spends more than three minutes on one job, and it needs a cache store shared
     * with the server that runs the workers, so treat `false` as a reason to fall back, not as an error.
     */
    public function isRunning(): bool
    {
        return collect([Cms::config()->queueName, Cms::config()->lowPriorityQueueName])
            ->unique()
            ->every(fn (string $queue) => Cache::has(self::CACHE_KEY_PREFIX.$queue));
    }
}
