<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Queue\Enums\JobStatus;
use CraftCms\Cms\Queue\Models\JobProgress as JobProgressModel;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Queue\ClearableQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use RuntimeException;

/**
 * Service for tracking job progress and status.
 */
#[Singleton]
readonly class JobProgress
{
    public function queued(string $uid, string $description, ?int $delay = null): void
    {
        $status = $delay && $delay > 0 ? JobStatus::Delayed : JobStatus::Pending;

        $this->upsertJob($uid, $status, [
            'progress' => 0,
            'description' => $description,
            'delay' => $delay,
        ]);
    }

    public function processing(string $uid): void
    {
        $this->upsertJob($uid, JobStatus::Reserved, [
            'progress' => 0,
        ]);
    }

    public function completed(string $uid): void
    {
        $this->upsertJob($uid, JobStatus::Done, [
            'progress' => 100,
            'dateCompleted' => now('utc'),
        ]);
    }

    public function failed(string $uid, ?string $description = null, ?string $error = null): void
    {
        $this->upsertJob($uid, JobStatus::Failed, array_filter([
            'progress' => 0,
            'description' => $description,
            'error' => $error,
            'dateFailed' => now('utc'),
        ]));
    }

    public function setProgress(string $uid, string $description, int $progress, ?string $label = null): void
    {
        $this->upsertJob($uid, JobStatus::Reserved, [
            'progress' => $progress,
            'progressLabel' => $label,
            'description' => $description,
        ]);
    }

    public function getProgress(string $uid): ?JobProgressModel
    {
        return JobProgressModel::query()
            ->where('uid', $uid)
            ->first();
    }

    public function getTotalJobs(): int
    {
        return JobProgressModel::count();
    }

    /**
     * @return Collection<int, JobProgressModel>
     */
    public function getJobInfo(?int $limit = null): Collection
    {
        return self::jobsQuery()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Builder<JobProgressModel>
     */
    public function jobsQuery(): Builder
    {
        $delay = match (DB::connection()->getDriverName()) {
            'mysql' => 'GREATEST(?, UNIX_TIMESTAMP(dateCreated) + delay)',
            'pgsql' => 'GREATEST(?, EXTRACT(EPOCH FROM "dateCreated") + delay)',
            'sqlite' => 'MAX(?, CAST(strftime(\'%s\', dateCreated) AS INTEGER) + delay)',
            default => throw new RuntimeException('Unsupported database driver: '.DB::connection()->getDriverName()),
        };

        return JobProgressModel::query()
            // Ignore finished jobs
            ->where('status', '!=', JobStatus::Done->value)
            // Failed jobs go last
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END DESC', [JobStatus::Failed->value])
            // Reserved jobs go first
            ->orderByRaw('CASE WHEN status = ? THEN 1 ELSE 0 END DESC', [JobStatus::Reserved->value])
            // Pending jobs go second
            ->orderByRaw('CASE WHEN status = ? THEN 1 ELSE 0 END DESC', [JobStatus::Pending->value])
            // Then by now or dateCreated + delay, with furthest delayed jobs first
            ->orderByRaw("$delay DESC", [now()->getTimestamp()])
            // Lastly by dateCreated
            ->orderBy('dateCreated');
    }

    /**
     * Gets all active jobs (pending, delayed, or reserved).
     *
     * @return Collection<int, JobProgressModel>
     */
    public function getAll(): Collection
    {
        return JobProgressModel::query()
            ->orderBy('dateCreated')
            ->get();
    }

    /**
     * Gets the job to be displayed in the sidebar
     */
    public function getDisplayedJob(): ?JobProgressModel
    {
        return JobProgressModel::query()
            ->orderBy('dateCreated')
            ->whereIn('status', [
                JobStatus::Reserved,
                JobStatus::Failed,
                JobStatus::Pending,
            ])
            ->whereNull('delay')
            ->first();
    }

    /**
     * Gets all active jobs (pending, delayed, or reserved).
     *
     * @return Collection<int, JobProgressModel>
     */
    public function getActive(): Collection
    {
        if (! Cms::isInstalled()) {
            return collect();
        }

        return JobProgressModel::query()
            ->whereIn('status', [
                JobStatus::Pending,
                JobStatus::Delayed,
                JobStatus::Reserved,
                JobStatus::Failed,
            ])
            ->get();
    }

    /**
     * Gets all jobs with a specific status.
     *
     * @return Collection<int, JobProgressModel>
     */
    public function getByStatus(JobStatus $status): Collection
    {
        return JobProgressModel::query()
            ->where('status', $status->value)
            ->get();
    }

    /**
     * @return Collection<int, JobProgressModel>
     */
    public function getFailed(): Collection
    {
        return $this->getByStatus(JobStatus::Failed);
    }

    public function hasReservedJobs(): bool
    {
        return $this->getByStatus(JobStatus::Reserved)->isNotEmpty();
    }

    public function hasPendingJobs(): bool
    {
        return $this->getByStatus(JobStatus::Pending)->isNotEmpty();
    }

    public function delete(string $uid): void
    {
        JobProgressModel::query()
            ->where('uid', $uid)
            ->delete();
    }

    public function clear(): void
    {
        JobProgressModel::query()
            ->whereIn('status', [
                JobStatus::Pending->value,
                JobStatus::Reserved->value,
                JobStatus::Delayed->value,
            ])
            ->delete();

        $queue = Queue::connection();

        if (! $queue instanceof ClearableQueue) {
            return;
        }

        $queue->clear(Cms::config()->queueName);

        if (Cms::config()->queueName !== Cms::config()->lowPriorityQueueName) {
            $queue->clear(Cms::config()->lowPriorityQueueName);
        }
    }

    public function clearCompleted(): void
    {
        JobProgressModel::query()
            ->where('status', JobStatus::Done->value)
            ->delete();
    }

    public function clearFailed(): void
    {
        JobProgressModel::query()
            ->where('status', JobStatus::Failed->value)
            ->delete();
    }

    public function updateStatus(string $uid, JobStatus $status): void
    {
        JobProgressModel::query()
            ->where('uid', $uid)
            ->update([
                'status' => $status->value,
                'dateUpdated' => now(),
            ]);
    }

    /**
     * Cancels a job by deleting its progress entry.
     *
     * The job will detect the absence of its progress entry and exit gracefully.
     */
    public function cancel(string $uid): void
    {
        $this->delete($uid);
    }

    /**
     * Checks if a job's progress entry exists.
     *
     * This is used by jobs to determine if they should continue running.
     * If the entry doesn't exist, the job was cancelled.
     */
    public function exists(string $uid): bool
    {
        return $this->getProgress($uid) !== null;
    }

    /**
     * Upserts a job entry.
     */
    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertJob(
        string $uid,
        JobStatus $status,
        array $attributes = [],
    ): void {
        DB::beginTransaction();

        $now = now();
        $exists = JobProgressModel::query()->where('uid', $uid)->exists();

        if (! $exists) {
            JobProgressModel::create(array_merge($attributes, [
                'uid' => $uid,
                'status' => $status->value,
                'dateCreated' => $now,
                'dateUpdated' => $now,
            ]));

            DB::commit();

            return;
        }

        $data = array_merge($attributes, [
            'status' => $status->value,
            'dateUpdated' => $now,
        ]);

        JobProgressModel::query()->where('uid', $uid)->update($data);

        DB::commit();
    }
}
