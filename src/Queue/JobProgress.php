<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Queue\Data\ProgressData;
use CraftCms\Cms\Queue\Enums\JobStatus;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Queue\ClearableQueue;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tpetry\QueryExpressions\Function\Time\Now;

/**
 * Service for tracking job progress and status.
 */
#[Singleton]
final readonly class JobProgress
{
    public function __construct(
        private DatabaseManager $db,
    ) {}

    public function queued(string $uid, string $description, ?int $delay = null): void
    {
        $status = $delay && $delay > 0 ? JobStatus::Delayed : JobStatus::Pending;

        $this->upsertJob($uid, $status, 0, $description, delay: $delay);
    }

    public function processing(string $uid): void
    {
        $this->upsertJob($uid, JobStatus::Reserved, 0);
    }

    public function completed(string $uid): void
    {
        $this->delete($uid);
    }

    public function failed(string $uid, ?string $description = null, ?string $error = null): void
    {
        $this->upsertJob($uid, JobStatus::Failed, 0, $description, null, $error);
    }

    public function setProgress(string $uid, string $description, int $progress, ?string $label = null): void
    {
        $this->upsertJob($uid, JobStatus::Reserved, $progress, $description, $label);
    }

    public function getProgress(string $uid): ?ProgressData
    {
        $row = $this->db->table(Table::JOBPROGRESS)
            ->where('uid', $uid)
            ->first();

        if ($row === null) {
            return null;
        }

        return ProgressData::from($row);
    }

    public function getTotalJobs(): int
    {
        return $this->db->table(Table::JOBPROGRESS)->count();
    }

    /**
     * @return Collection<ProgressData>
     */
    public function getJobInfo(?int $limit = null): Collection
    {
        $delay = match (DB::connection()->getDriverName()) {
            'mysql' => 'GREATEST(?, UNIX_TIMESTAMP(dateCreated) + delay)',
            'pgsql' => 'GREATEST(?, EXTRACT(EPOCH FROM "dateCreated") + delay)',
            default => throw new RuntimeException('Unsupported database driver: '.DB::connection()->getDriverName()),
        };

        return $this->db->table(Table::JOBPROGRESS)
            // Failed jobs go last
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END DESC', [JobStatus::Failed->value])
            // Reserved jobs go first
            ->orderByRaw('CASE WHEN status= ? THEN 1 ELSE 0 END DESC', [JobStatus::Reserved])
            // Pending jobs go second
            ->orderByRaw('CASE WHEN status= ? THEN 1 ELSE 0 END DESC', [JobStatus::Pending])
            // Then by now or dateCreated + delay
            ->orderByRaw($delay, [now()->getTimestamp()])
            // Lastly by dateCreated
            ->orderBy('dateCreated')
            ->limit($limit)
            ->get()
            ->map(fn (object $row) => ProgressData::from($row));
    }

    /**
     * Gets all active jobs (pending, delayed, or reserved).
     *
     * @return Collection<ProgressData>
     */
    public function getAll(): Collection
    {
        return $this->db->table(Table::JOBPROGRESS)
            ->orderBy('dateCreated')
            ->get()
            ->map(fn (object $row) => ProgressData::from($row));
    }

    /**
     * Gets all active jobs (pending, delayed, or reserved).
     *
     * @return Collection<ProgressData>
     */
    public function getActive(): Collection
    {
        return $this->db->table(Table::JOBPROGRESS)
            ->whereIn('status', [
                JobStatus::Pending,
                JobStatus::Delayed,
                JobStatus::Reserved,
                JobStatus::Failed,
            ])
            ->orderBy('dateCreated')
            ->get()
            ->map(fn (object $row) => ProgressData::from($row));
    }

    /**
     * Gets all jobs with a specific status.
     *
     * @return Collection<ProgressData>
     */
    public function getByStatus(JobStatus $status): Collection
    {
        return $this->db->table(Table::JOBPROGRESS)
            ->where('status', $status->value)
            ->orderBy('dateCreated')
            ->get()
            ->map(fn (object $row) => ProgressData::from($row));
    }

    /**
     * @return Collection<ProgressData>
     */
    public function getFailed(): Collection
    {
        return $this->getByStatus(JobStatus::Failed);
    }

    public function delete(string $uid): void
    {
        $this->db->table(Table::JOBPROGRESS)
            ->where('uid', $uid)
            ->delete();
    }

    public function clear(): void
    {
        $this->db->table(Table::JOBPROGRESS)->delete();

        $queue = Queue::connection();

        if ($queue instanceof ClearableQueue) {
            $queue->clear(Cms::config()->queueName);
            $queue->clear(Cms::config()->lowPriorityQueueName);
        }
    }

    public function clearCompleted(): void
    {
        $this->db->table(Table::JOBPROGRESS)
            ->where('status', JobStatus::Done->value)
            ->delete();
    }

    public function updateStatus(string $uid, JobStatus $status): void
    {
        $this->db->table(Table::JOBPROGRESS)
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
    private function upsertJob(
        string $uid,
        JobStatus $status,
        int $progress,
        ?string $description = null,
        ?string $label = null,
        ?string $error = null,
        ?int $delay = null,
    ): void {
        DB::beginTransaction();

        $now = now();
        $exists = $this->db->table(Table::JOBPROGRESS)->where('uid', $uid)->exists();

        if (! $exists) {
            $this->db->table(Table::JOBPROGRESS)->insert([
                'uid' => $uid,
                'status' => $status->value,
                'description' => $description,
                'progress' => $progress,
                'progressLabel' => $label,
                'delay' => $delay,
                'error' => $error,
                'dateCreated' => $now,
                'dateUpdated' => $now,
            ]);

            return;
        }

        $data = [
            'status' => $status->value,
            'progress' => $progress,
            'progressLabel' => $label,
            'error' => $error,
            'dateUpdated' => $now,
        ];

        if (! is_null($description)) {
            $data['description'] = $description;
        }

        $this->db->table(Table::JOBPROGRESS)->where('uid', $uid)->update($data);
    }
}
