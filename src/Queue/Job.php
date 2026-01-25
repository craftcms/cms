<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Queue\Contracts\DescribableJob;
use CraftCms\Cms\Queue\Middleware\CheckShouldRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Base class for Craft queue jobs.
 *
 * Provides progress tracking integration with JobProgressService.
 */
abstract class Job implements DescribableJob, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The job's human-readable description.
     */
    protected ?string $description = null;

    /**
     * The number of seconds the job can run before timing out.
     * This maps to Laravel's TTR equivalent.
     */
    public int $timeout = 300;

    public function __construct()
    {
        $this->queue = Cms::config()->queueName;
    }

    /**
     * Execute the job.
     */
    abstract public function handle(): void;

    public function getDescription(): string
    {
        return $this->description ?? $this->defaultDescription();
    }

    /**
     * Returns a default description for the job.
     */
    protected function defaultDescription(): string
    {
        return static::class;
    }

    /**
     * Updates the job progress (0-100) with an optional label.
     *
     * Creates or updates the progress entry in the database.
     * Progress is displayed in the Control Panel Queue Manager.
     */
    protected function setProgress(int $progress, ?string $label = null): void
    {
        $uuid = $this->job?->uuid();

        if ($uuid === null) {
            return;
        }

        app(JobProgressService::class)->setProgress(
            uid: $uuid,
            description: $this->getDescription(),
            progress: $progress,
            label: $label,
        );
    }

    /**
     * Returns the middleware that should be applied to the job.
     *
     * @return array<object>
     */
    public function middleware(): array
    {
        return [new CheckShouldRun];
    }

    /**
     * Determines if the job should still run.
     *
     * Returns false if the job's progress entry was deleted (cancelled).
     * Jobs can override this to add custom cancellation logic.
     */
    public function shouldStillRun(): bool
    {
        $uuid = $this->job?->uuid();

        if ($uuid === null) {
            return true;
        }

        return app(JobProgressService::class)->exists($uuid);
    }
}
