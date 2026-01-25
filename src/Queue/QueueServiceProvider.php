<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Queue\Contracts\DescribableJob;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class QueueServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerEventListeners();
    }

    private function registerEventListeners(): void
    {
        // Track when a job is queued
        Event::listen(JobQueued::class, function (JobQueued $event): void {
            $this->trackQueued($event);
        });

        // Track when a job starts processing
        Event::listen(JobProcessing::class, function (JobProcessing $event): void {
            $this->trackProcessing($event);
        });

        // Track when a job completes successfully
        Event::listen(JobProcessed::class, function (JobProcessed $event): void {
            $this->trackCompleted($event);
        });

        // Track when a job fails
        Event::listen(JobFailed::class, function (JobFailed $event): void {
            $this->trackFailed($event);
        });
    }

    private function trackQueued(JobQueued $event): void
    {
        if (! $this->shouldTrackQueue($event->queue)) {
            return;
        }

        $job = $event->job;
        $uuid = $event->payload()['uuid'] ?? $this->getJobUuid($job);

        if ($uuid === null) {
            return;
        }

        $description = $this->getJobDescription($job);
        $delayed = $event->delay !== null && $event->delay > 0;

        $this->app->make(JobProgress::class)->queued($uuid, $description, $delayed);
    }

    private function trackProcessing(JobProcessing $event): void
    {
        if (! $this->shouldTrackQueue($event->job->getQueue())) {
            return;
        }

        $uuid = $this->getQueueJobUuid($event->job);

        if ($uuid === null) {
            return;
        }

        $description = $this->getQueueJobDescription($event->job);

        $this->app->make(JobProgress::class)->processing($uuid, $description);
    }

    private function trackCompleted(JobProcessed $event): void
    {
        if (! $this->shouldTrackQueue($event->job->getQueue())) {
            return;
        }

        $uuid = $this->getQueueJobUuid($event->job);

        if ($uuid === null) {
            return;
        }

        $this->app->make(JobProgress::class)->completed($uuid);
    }

    private function trackFailed(JobFailed $event): void
    {
        if (! $this->shouldTrackQueue($event->job->getQueue())) {
            return;
        }

        $uuid = $this->getQueueJobUuid($event->job);

        if ($uuid === null) {
            return;
        }

        $description = $this->getQueueJobDescription($event->job);
        $error = $event->exception->getMessage();

        $this->app->make(JobProgress::class)->failed($uuid, $description, $error);
    }

    private function shouldTrackQueue(?string $queueName): bool
    {
        return in_array($queueName, Cms::config()->trackedQueueNames, true);
    }

    private function getJobUuid(mixed $job): ?string
    {
        // For JobQueued event, $job is the actual job instance
        if (is_object($job) && property_exists($job, 'uuid')) {
            return $job->uuid;
        }

        return null;
    }

    private function getQueueJobUuid(mixed $job): ?string
    {
        if (! method_exists($job, 'uuid')) {
            return null;
        }

        return $job->uuid();
    }

    private function getJobDescription(mixed $job): string
    {
        if ($job instanceof DescribableJob) {
            return $job->getDescription();
        }

        if (is_object($job)) {
            return $job::class;
        }

        return 'Unknown job';
    }

    private function getQueueJobDescription(mixed $job): string
    {
        // Try to get the underlying job instance
        if (method_exists($job, 'payload')) {
            $payload = $job->payload();

            if (isset($payload['displayName'])) {
                return $payload['displayName'];
            }
        }

        // Try to resolve the actual job
        if (method_exists($job, 'resolveName')) {
            return $job->resolveName();
        }

        return 'Unknown job';
    }
}
