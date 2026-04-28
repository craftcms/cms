<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static void queued(string $uid, string $description, int|null $delay = null)
 * @method static void processing(string $uid)
 * @method static void completed(string $uid)
 * @method static void failed(string $uid, string|null $description = null, string|null $error = null)
 * @method static void setProgress(string $uid, string $description, int $progress, string|null $label = null)
 * @method static \CraftCms\Cms\Queue\Models\JobProgress|null getProgress(string $uid)
 * @method static int getTotalJobs()
 * @method static \Illuminate\Support\Collection<\CraftCms\Cms\Queue\Models\JobProgress> getJobInfo(int|null $limit = null)
 * @method static \Illuminate\Database\Eloquent\Builder<\CraftCms\Cms\Queue\Models\JobProgress> jobsQuery()
 * @method static \Illuminate\Support\Collection<\CraftCms\Cms\Queue\Models\JobProgress> getAll()
 * @method static \CraftCms\Cms\Queue\Models\JobProgress|null getDisplayedJob()
 * @method static \Illuminate\Support\Collection<\CraftCms\Cms\Queue\Models\JobProgress> getActive()
 * @method static \Illuminate\Support\Collection<\CraftCms\Cms\Queue\Models\JobProgress> getByStatus(\CraftCms\Cms\Queue\Enums\JobStatus $status)
 * @method static \Illuminate\Support\Collection<\CraftCms\Cms\Queue\Models\JobProgress> getFailed()
 * @method static bool hasReservedJobs()
 * @method static bool hasPendingJobs()
 * @method static void delete(string $uid)
 * @method static void clear()
 * @method static void clearCompleted()
 * @method static void updateStatus(string $uid, \CraftCms\Cms\Queue\Enums\JobStatus $status)
 * @method static void cancel(string $uid)
 * @method static bool exists(string $uid)
 *
 * @see \CraftCms\Cms\Queue\JobProgress
 */
class JobProgress extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Queue\JobProgress::class;
    }
}
