<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue;

use Craft;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Queue\Contracts\DescribableJob;
use CraftCms\Cms\Queue\JobProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Wraps legacy Craft queue jobs (implementing JobInterface) as Laravel ShouldQueue jobs.
 *
 * This allows existing plugins and legacy code using the Yii2-style jobs to continue
 * working with Laravel's queue system.
 *
 * @internal
 * @deprecated 6.0.0
 */
final class LegacyJobWrapper implements DescribableJob, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    public function __construct(
        private readonly JobInterface $legacyJob,
    ) {
        $this->queue = Cms::config()->queueName;

        if (method_exists($this->legacyJob, 'getTtr')) {
            $this->timeout = $this->legacyJob->getTtr();
        }
    }

    public function handle(): void
    {
        $this->legacyJob->execute(Craft::$app->getQueue());
    }

    public function getDescription(): string
    {
        return $this->legacyJob->getDescription() ?? $this->legacyJob::class;
    }

    /**
     * Returns the legacy job instance.
     */
    public function getLegacyJob(): JobInterface
    {
        return $this->legacyJob;
    }

    /**
     * Updates the progress for this job.
     *
     * Called by LegacyQueueAdapter when the legacy job calls setProgress().
     */
    public function updateProgress(int $progress, ?string $label = null): void
    {
        $uuid = $this->job?->uuid();

        if ($uuid === null) {
            return;
        }

        app(JobProgress::class)->setProgress(
            uid: $uuid,
            description: $this->getDescription(),
            progress: $progress,
            label: $label,
        );
    }
}
