<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use CraftCms\Cms\Queue\Data\ProgressData;
use CraftCms\Cms\Queue\Enums\JobStatus;
use CraftCms\Cms\Queue\JobProgress;
use CraftCms\Cms\Utility\Utility;

use function CraftCms\Cms\t;

/**
 * Queue manager is a utility used for managing jobs in the Queue.
 *
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 */
final class QueueManager extends Utility
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function displayName(): string
    {
        return t('Queue Manager');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function id(): string
    {
        return 'queue-manager';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function icon(): string
    {
        return 'play';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function toolbarHtml(): string
    {
        $progressService = app(JobProgress::class);

        return view('c::utilities.queue-manager.toolbar', [
            'activeJob' => self::getActiveJob($progressService),
            'jobs' => $progressService->getJobInfo(),
        ])->toHtml();
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function footerHtml(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function contentHtml(): string
    {
        $progressService = app(JobProgress::class);
        $jobsData = app(JobProgress::class)->getJobInfo();

        return view('c::utilities.queue-manager.content', [
            'initialData' => $jobsData,
            'activeJob' => self::getActiveJob($progressService),
            'hasReservedJobs' => $progressService->getByStatus(JobStatus::Reserved)->count() > 0,
            'hasWaitingJobs' => $progressService->getByStatus(JobStatus::Pending)->count() > 0,
            'totalJobs' => $progressService->getTotalJobs(),
        ])->toHtml();
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
