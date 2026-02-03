<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
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
            'activeJob' => null,
            'jobs' => $progressService->getJobInfo(),
        ])->toHtml();
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function footerHtml(): string
    {
        // @TODO
        return Craft::$app->getView()->renderTemplate('_components/utilities/QueueManager/footer.twig');
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
            'hasReservedJobs' => $progressService->getByStatus(JobStatus::Reserved)->count() > 0,
            'hasWaitingJobs' => $progressService->getByStatus(JobStatus::Pending)->count() > 0,
            'totalJobs' => $progressService->getTotalJobs(),
        ])->render();
    }
}
