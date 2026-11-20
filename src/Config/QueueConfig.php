<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

/**
 * Settings for Craft’s queue jobs.
 *
 * @since 6.0.0
 */
class QueueConfig extends BaseConfig
{
    /**
     * Whether Craft should run pending queue jobs automatically when someone visits the control panel.
     *
     * If disabled, run a Laravel queue worker, such as `php artisan queue:work` kept running by Supervisor or systemd, or run
     * `queue:work --once` every minute. Include `name` and `lowPriorityName` in the worker’s `--queue` option when they’re
     * customized. Disable it on servers where PHP’s `flush()` doesn’t work, such as Win32 or Apache with mod_deflate or
     * mod_gzip.
     */
    public bool $runAutomatically = true;

    /** The name of the queue Craft jobs are sent to. */
    public string $name = 'default';

    /** The name of the queue lower-priority Craft jobs are sent to. */
    public string $lowPriorityName = 'default';

    /** @var list<string> The names of the queues whose jobs have their progress tracked. */
    public array $trackedNames = ['default'];

    public function runAutomatically(bool $value = true): self
    {
        $this->runAutomatically = $value;

        return $this;
    }

    public function name(string $value): self
    {
        $this->name = $value;

        return $this;
    }

    public function lowPriorityName(string $value): self
    {
        $this->lowPriorityName = $value;

        return $this;
    }

    /** @param list<string> $value */
    public function trackedNames(array $value): self
    {
        $this->trackedNames = array_values($value);

        return $this;
    }
}
