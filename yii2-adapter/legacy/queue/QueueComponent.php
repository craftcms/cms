<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue;

use craft\helpers\Queue as QueueHelper;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Queue\Enums\JobStatus;
use CraftCms\Cms\Queue\JobProgress;
use CraftCms\Cms\Queue\Models\JobProgress as JobProgressModel;
use CraftCms\Cms\Support\Arr;
use Illuminate\Contracts\Queue\Queue as LaravelQueue;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Illuminate\Support\Facades\Artisan;
use yii\base\Component;

/**
 * Provides the Craft::$app->getQueue() API using Laravel's queue system.
 *
 * This component maintains backwards compatibility for legacy code that accesses
 * the queue via Craft::$app->getQueue().
 *
 * @deprecated 6.0.0
 */
class QueueComponent extends Component implements QueueInterface
{
    /**
     * @var int Default time-to-reserve for jobs (in seconds).
     */
    public $ttr = 300;

    /**
     * Pushes a job to the queue.
     *
     * @param  JobInterface  $job  The job to execute.
     * @param  int|null  $priority  Ignored - Laravel doesn't support priority.
     * @param  int|null  $delay  The execution delay (in seconds).
     * @param  int|null  $ttr  The maximum time the job can run before timing out.
     * @return string|null Always returns null - Laravel assigns UUID internally.
     */
    public function push($job, ?int $priority = null, ?int $delay = null, ?int $ttr = null): ?string
    {
        return QueueHelper::push($job, $priority, $delay, $ttr);
    }

    /**
     * Runs the queue worker.
     *
     * @deprecated Use Laravel's `php artisan queue:work` command instead.
     */
    public function run(bool $repeat = false, int $timeout = 0): mixed
    {
        Artisan::call('queue:work', Arr::whereNotNull([
            '--queue' => implode(',', array_unique([Cms::config()->queueName, Cms::config()->lowPriorityQueueName])),
            '--stop-when-empty' => !$repeat,
            '--rest' => $timeout,
        ]));

        return null;
    }

    /**
     * Retries a failed job.
     *
     * @param  string  $id  The failed job ID.
     */
    public function retry(string $id): void
    {
        $failedJobProvider = app(FailedJobProviderInterface::class);
        $failedJob = $failedJobProvider->find($id);

        if ($failedJob === null) {
            return;
        }

        $queue = app(LaravelQueue::class);
        $queue->pushRaw($failedJob->payload, $failedJob->queue);
        $failedJobProvider->forget($id);

        // Clean up the failed job entry from jobprogress
        app(JobProgress::class)->delete($id);
    }

    /**
     * Retries all failed jobs.
     */
    public function retryAll(): void
    {
        $failedJobProvider = app(FailedJobProviderInterface::class);
        $queue = app(LaravelQueue::class);
        $progressService = app(JobProgress::class);

        foreach ($failedJobProvider->all() as $failedJob) {
            $queue->pushRaw($failedJob->payload, $failedJob->queue);
            $failedJobProvider->forget($failedJob->id);

            // Clean up the failed job entry from jobprogress
            $progressService->delete($failedJob->id);
        }
    }

    /**
     * Sets the progress for the currently reserved job.
     *
     * @param  int  $progress  The job progress (1-100).
     * @param  string|null  $label  The progress label.
     *
     * @deprecated Progress should be set from within the job itself.
     */
    public function setProgress(int $progress, ?string $label = null): void
    {
        // This method is no longer used in the Laravel queue system.
        // Progress is now set from within jobs via JobProgressService.
    }

    /**
     * Returns whether there are any waiting jobs.
     */
    public function getHasWaitingJobs(): bool
    {
        return app(JobProgress::class)->getByStatus(JobStatus::Pending)->count() > 0;
    }

    /**
     * Returns whether there are any reserved (in-progress) jobs.
     */
    public function getHasReservedJobs(): bool
    {
        return app(JobProgress::class)->getByStatus(JobStatus::Reserved)->isNotEmpty();
    }

    /**
     * Returns the total number of waiting jobs in the queue.
     */
    public function getTotalJobs(): int
    {
        return app(JobProgress::class)->getTotalJobs();
    }

    /**
     * Returns info about active jobs with progress.
     *
     * @param  int|null  $limit  Maximum number of jobs to return.
     * @return array<int, array{
     *     id: string,
     *     status: int,
     *     progress: int,
     *     progressLabel: string|null,
     *     description: string
     * }>
     */
    public function getJobInfo(?int $limit = null): array
    {
        return app(JobProgress::class)->getJobInfo($limit)->map(fn(JobProgressModel $job) => [
            'id' => $job->uid,
            'status' => $job->status->value,
            'progress' => $job->progress,
            'progressLabel' => $job->label,
            'description' => $job->description,
            'error' => $job->error,
        ])->values()->toArray();
    }

    /**
     * Returns detailed info about a single job.
     *
     * @param  string  $id  The job ID (UUID).
     * @return array{
     *     status: int,
     *     progress: int,
     *     progressLabel: string|null,
     *     description: string,
     *     error: string|null
     * }
     */
    public function getJobDetails(string $id): array
    {
        $progress = app(JobProgress::class)->getProgress($id);

        if ($progress === null) {
            // Check failed jobs
            $failedJobProvider = app(FailedJobProviderInterface::class);
            $failedJob = $failedJobProvider->find($id);

            if ($failedJob !== null) {
                return [
                    'status' => JobStatus::Failed->value,
                    'progress' => 0,
                    'progressLabel' => null,
                    'description' => 'Failed job',
                    'error' => $failedJob->exception,
                ];
            }

            return [
                'status' => JobStatus::Pending->value,
                'progress' => 0,
                'progressLabel' => null,
                'description' => 'Unknown job',
                'error' => null,
            ];
        }

        return [
            'status' => $progress->status->value,
            'progress' => $progress->progress,
            'progressLabel' => $progress->label,
            'description' => $progress->description,
            'error' => $progress->error,
        ];
    }

    /**
     * Releases a reserved job back to the queue.
     *
     * Deletes the job's progress entry - the job will detect this
     * and exit gracefully on its next cancellation check.
     *
     * @param  string  $id  The job ID.
     */
    public function release(string $id): void
    {
        app(JobProgress::class)->cancel($id);
    }

    /**
     * Releases all reserved jobs back to the queue.
     *
     * Clears all job progress entries - jobs will detect this
     * and exit gracefully on their next cancellation check.
     */
    public function releaseAll(): void
    {
        app(JobProgress::class)->clear();
    }
}
