<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use CraftCms\Cms\Queue\Enums\JobStatus;
use CraftCms\Cms\Queue\JobProgress;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Utility\Utility;
use Override;

use function CraftCms\Cms\t;

/**
 * Queue manager is a utility used for managing jobs in the Queue.
 *
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 */
final class QueueManager extends Utility
{
    #[Override]
    public static function displayName(): string
    {
        return t('Queue Manager');
    }

    #[Override]
    public static function id(): string
    {
        return 'queue-manager';
    }

    #[Override]
    public static function icon(): string
    {
        return 'play';
    }

    #[Override]
    public static function toolbarHtml(): string
    {
        $progressService = app(JobProgress::class);

        return Html::tag('QueueManagerToolbar', attributes: [
            ':activeJob' => self::getActiveJob($progressService),
            ':jobs' => $progressService->getJobInfo(),
        ]);
    }

    #[Override]
    public static function footerHtml(): string
    {
        return '';
    }

    #[Override]
    public static function contentHtml(): string
    {
        $progressService = app(JobProgress::class);
        $jobsData = app(JobProgress::class)->getJobInfo();

        return Html::tag('QueueManager', attributes: [
            ':initialData' => $jobsData,
            ':activeJob' => self::getActiveJob($progressService),
            ':hasReservedJobs' => $progressService->getByStatus(JobStatus::Reserved)->count() > 0,
            ':hasWaitingJobs' => $progressService->getByStatus(JobStatus::Pending)->count() > 0,
            ':totalJobs' => $progressService->getTotalJobs(),
        ]);
    }

    private static function getActiveJob(JobProgress $progressService): ?\CraftCms\Cms\Queue\Models\JobProgress
    {
        $jobId = request()->route('extra');

        if ($jobId) {
            return $progressService->jobsQuery()
                ->where('uid', $jobId)
                ->first();
        }

        return null;
    }
}
