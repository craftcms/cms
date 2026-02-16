<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use CraftCms\Cms\Queue\Data\ProgressData;
use CraftCms\Cms\Queue\Enums\JobStatus;
use CraftCms\Cms\Queue\JobProgress;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Utility\Utility;

use function CraftCms\Cms\t;

/**
 * Queue manager is a utility used for managing jobs in the Queue.
 *
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 */
final class QueueManager extends Utility
{
    #[\Override]
    public static function displayName(): string
    {
        return t('Queue Manager');
    }

    #[\Override]
    public static function id(): string
    {
        return 'queue-manager';
    }

    #[\Override]
    public static function icon(): string
    {
        return 'play';
    }

    #[\Override]
    public static function toolbarHtml(): string
    {
        $progressService = app(JobProgress::class);

        return Html::tag('QueueManagerToolbar', options: [
            ':activeJob' => self::getActiveJob($progressService),
            ':jobs' => $progressService->getJobInfo(),
        ]);
    }

    #[\Override]
    public static function footerHtml(): string
    {
        return '';
    }

    #[\Override]
    public static function contentHtml(): string
    {
        $progressService = app(JobProgress::class);
        $jobsData = app(JobProgress::class)->getJobInfo();

        return Html::tag('QueueManager', options: [
            ':initialData' => $jobsData,
            ':activeJob' => self::getActiveJob($progressService),
            ':hasReservedJobs' => $progressService->getByStatus(JobStatus::Reserved)->count() > 0,
            ':hasWaitingJobs' => $progressService->getByStatus(JobStatus::Pending)->count() > 0,
            ':totalJobs' => $progressService->getTotalJobs(),
        ]);
    }

    private static function getActiveJob(JobProgress $progressService): ?ProgressData
    {
        $jobId = request()->route('extra');

        if ($jobId) {
            $activeJob = $progressService->jobsQuery()
                ->where('uid', $jobId)
                ->first();

            if ($activeJob) {
                return ProgressData::from($activeJob);
            }
        }

        return null;
    }
}
