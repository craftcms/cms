<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Queue\Data\ProgressData;
use CraftCms\Cms\Queue\Enums\JobStatus;
use CraftCms\Cms\Support\Arr;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;

/**
 * Service for tracking job progress and status.
 */
#[Singleton]
final readonly class JobProgress
{
    private const string CACHE_PREFIX = 'craft_job_progress:';

    private const int CACHE_TTL = 3600;

    public function __construct(
        private DatabaseManager $db,
        private CacheRepository $cache,
    ) {}

    public function queued(string $uid, string $description, bool $delayed = false): void
    {
        $status = $delayed ? JobStatus::Delayed : JobStatus::Pending;

        $this->upsertJob($uid, $description, $status, 0, null, null);
    }

    public function processing(string $uid, string $description): void
    {
        $this->upsertJob($uid, $description, JobStatus::Reserved, 0, null, null);
    }

    public function completed(string $uid): void
    {
        $this->delete($uid);
    }

    public function failed(string $uid, string $description, ?string $error = null): void
    {
        $this->upsertJob($uid, $description, JobStatus::Failed, 0, null, $error);
    }

    public function setProgress(string $uid, string $description, int $progress, ?string $label = null): void
    {
        $this->upsertJob($uid, $description, JobStatus::Reserved, $progress, $label, null);
    }

    public function getProgress(string $uid): ?ProgressData
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

        $data = ProgressData::from([
            'uid' => $row->uid,
            'description' => $row->description,
            'status' => (int) $row->status,
            'progress' => (int) $row->progress,
            'label' => $row->progressLabel,
            'error' => $row->error,
        ]);

        $this->cache->put(self::CACHE_PREFIX.$uid, $data, self::CACHE_TTL);

        return $data;
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
        /** @phpstan-ignore return.type */
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

        $this->cache->forget(self::CACHE_PREFIX.$uid);
    }

    public function clear(): void
    {
        $uids = $this->db->table(Table::JOBPROGRESS)->pluck('uid');

        $this->db->table(Table::JOBPROGRESS)->delete();

        foreach ($uids as $uid) {
            $this->cache->forget(self::CACHE_PREFIX.$uid);
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

        $data = [
            'uid' => $uid,
            'description' => $description,
            'status' => $status->value,
            'progress' => $progress,
            'progressLabel' => $label,
            'error' => $error,
            'dateCreated' => $now,
            'dateUpdated' => $now,
        ];

        $this->db->table(Table::JOBPROGRESS)->upsert(
            values: $data,
            uniqueBy: ['uid'],
            update: Arr::only($data, ['description', 'status', 'progress', 'progressLabel', 'error', 'dateUpdated']),
        );

        $this->cache->put(
            self::CACHE_PREFIX.$uid,
            ProgressData::from($data),
            self::CACHE_TTL,
        );
    }
}
