<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue;

use CraftCms\Cms\Database\Table;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;

/**
 * Service for tracking job progress and status.
 *
 * Uses database for persistent storage and cache for fast access
 * during CP polling.
 */
#[Singleton]
final readonly class JobProgressService
{
    private const string CACHE_PREFIX = 'craft_job_progress:';

    private const int CACHE_TTL = 3600;

    public function __construct(
        private DatabaseManager $db,
        private CacheRepository $cache,
    ) {}

    /**
     * Tracks a new job being queued.
     *
     * @param  string  $uid  The job UUID
     * @param  string  $description  The job description
     * @param  bool  $delayed  Whether the job is delayed
     */
    public function trackQueued(string $uid, string $description, bool $delayed = false): void
    {
        $status = $delayed ? JobStatus::Delayed : JobStatus::Pending;
        $this->upsertJob($uid, $description, $status, 0, null, null);
    }

    /**
     * Tracks a job starting processing.
     *
     * @param  string  $uid  The job UUID
     * @param  string  $description  The job description
     */
    public function trackProcessing(string $uid, string $description): void
    {
        $this->upsertJob($uid, $description, JobStatus::Reserved, 0, null, null);
    }

    /**
     * Tracks a job completing successfully.
     *
     * @param  string  $uid  The job UUID
     */
    public function trackCompleted(string $uid): void
    {
        // Remove the job from tracking - it's done
        $this->delete($uid);
    }

    /**
     * Tracks a job failing.
     *
     * @param  string  $uid  The job UUID
     * @param  string  $description  The job description
     * @param  string|null  $error  The error message
     */
    public function trackFailed(string $uid, string $description, ?string $error = null): void
    {
        $this->upsertJob($uid, $description, JobStatus::Failed, 0, null, $error);
    }

    /**
     * Sets/updates the progress for a job (upsert pattern).
     *
     * @param  string  $uid  The job UUID
     * @param  string  $description  The job description
     * @param  int  $progress  Progress percentage (0-100)
     * @param  string|null  $label  Optional progress label
     */
    public function setProgress(string $uid, string $description, int $progress, ?string $label = null): void
    {
        $this->upsertJob($uid, $description, JobStatus::Reserved, $progress, $label, null);
    }

    /**
     * Gets the progress for a specific job.
     *
     * @return array{uid: string, description: string, status: int, progress: int, progressLabel: string|null, error: string|null}|null
     */
    public function getProgress(string $uid): ?array
    {
        $cached = $this->cache->get(self::CACHE_PREFIX.$uid);

        if ($cached !== null) {
            return $cached;
        }

        $row = $this->db->table(Table::JOBPROGRESS)
            ->where('uid', $uid)
            ->first();

        if ($row === null) {
            return null;
        }

        $data = [
            'uid' => $row->uid,
            'description' => $row->description,
            'status' => (int) $row->status,
            'progress' => (int) $row->progress,
            'progressLabel' => $row->progressLabel,
            'error' => $row->error,
        ];

        $this->cache->put(self::CACHE_PREFIX.$uid, $data, self::CACHE_TTL);

        return $data;
    }

    /**
     * Gets all active jobs (pending, delayed, or reserved).
     *
     * @return Collection<int, array{uid: string, description: string, status: int, progress: int, progressLabel: string|null, error: string|null}>
     */
    public function getActive(): Collection
    {
        /** @phpstan-ignore return.type */
        return $this->db->table(Table::JOBPROGRESS)
            ->whereIn('status', [
                JobStatus::Pending->value,
                JobStatus::Delayed->value,
                JobStatus::Reserved->value,
            ])
            ->orderBy('dateCreated')
            ->get()
            ->map(fn (object $row) => [
                'uid' => (string) $row->uid,
                'description' => (string) $row->description,
                'status' => (int) $row->status,
                'progress' => (int) $row->progress,
                'progressLabel' => $row->progressLabel !== null ? (string) $row->progressLabel : null,
                'error' => $row->error !== null ? (string) $row->error : null,
            ])
            ->values();
    }

    /**
     * Gets all jobs with a specific status.
     *
     * @return Collection<int, array{uid: string, description: string, status: int, progress: int, progressLabel: string|null, error: string|null}>
     */
    public function getByStatus(JobStatus $status): Collection
    {
        /** @phpstan-ignore return.type */
        return $this->db->table(Table::JOBPROGRESS)
            ->where('status', $status->value)
            ->orderBy('dateCreated')
            ->get()
            ->map(fn (object $row) => [
                'uid' => (string) $row->uid,
                'description' => (string) $row->description,
                'status' => (int) $row->status,
                'progress' => (int) $row->progress,
                'progressLabel' => $row->progressLabel !== null ? (string) $row->progressLabel : null,
                'error' => $row->error !== null ? (string) $row->error : null,
            ])
            ->values();
    }

    /**
     * Gets all failed jobs.
     *
     * @return Collection<int, array{uid: string, description: string, status: int, progress: int, progressLabel: string|null, error: string|null}>
     */
    public function getFailed(): Collection
    {
        return $this->getByStatus(JobStatus::Failed);
    }

    /**
     * Deletes a job progress entry.
     */
    public function delete(string $uid): void
    {
        $this->db->table(Table::JOBPROGRESS)
            ->where('uid', $uid)
            ->delete();

        $this->cache->forget(self::CACHE_PREFIX.$uid);
    }

    /**
     * Clears all job progress entries.
     */
    public function clear(): void
    {
        $uids = $this->db->table(Table::JOBPROGRESS)->pluck('uid');

        $this->db->table(Table::JOBPROGRESS)->delete();

        foreach ($uids as $uid) {
            $this->cache->forget(self::CACHE_PREFIX.$uid);
        }
    }

    /**
     * Clears completed jobs (keeps failed for retry).
     */
    public function clearCompleted(): void
    {
        $this->db->table(Table::JOBPROGRESS)
            ->where('status', JobStatus::Done->value)
            ->delete();
    }

    /**
     * Updates the status of a job.
     */
    public function updateStatus(string $uid, JobStatus $status): void
    {
        $this->db->table(Table::JOBPROGRESS)
            ->where('uid', $uid)
            ->update([
                'status' => $status->value,
                'dateUpdated' => now(),
            ]);

        $this->cache->forget(self::CACHE_PREFIX.$uid);
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
        string $description,
        JobStatus $status,
        int $progress,
        ?string $label,
        ?string $error,
    ): void {
        $now = now();

        $this->db->table(Table::JOBPROGRESS)->upsert(
            [
                'uid' => $uid,
                'description' => $description,
                'status' => $status->value,
                'progress' => $progress,
                'progressLabel' => $label,
                'error' => $error,
                'dateCreated' => $now,
                'dateUpdated' => $now,
            ],
            ['uid'],
            [
                'description' => $description,
                'status' => $status->value,
                'progress' => $progress,
                'progressLabel' => $label,
                'error' => $error,
                'dateUpdated' => $now,
            ],
        );

        $this->cache->put(
            self::CACHE_PREFIX.$uid,
            [
                'uid' => $uid,
                'description' => $description,
                'status' => $status->value,
                'progress' => $progress,
                'progressLabel' => $label,
                'error' => $error,
            ],
            self::CACHE_TTL,
        );
    }
}
