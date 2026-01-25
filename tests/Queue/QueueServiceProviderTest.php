<?php

declare(strict_types=1);

use CraftCms\Cms\Queue\JobProgressService;
use CraftCms\Cms\Queue\JobStatus;
use CraftCms\Cms\Queue\QueueServiceProvider;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;

it('registers JobProgressService as singleton', function () {
    $service1 = app(JobProgressService::class);
    $service2 = app(JobProgressService::class);

    expect($service1)->toBeInstanceOf(JobProgressService::class)
        ->and($service1)->toBe($service2);
});

it('is registered in the application', function () {
    $providers = collect(app()->getLoadedProviders())
        ->keys()
        ->all();

    expect($providers)->toContain(QueueServiceProvider::class);
});

it('cleans up progress when job is processed', function () {
    $progressService = app(JobProgressService::class);
    $uid = 'test-cleanup-processed';

    $progressService->setProgress($uid, 'Test Job', 50);

    expect($progressService->getProgress($uid))->not->toBeNull();

    $mockJob = new readonly class($uid)
    {
        public function __construct(private string $uid) {}

        public function uuid(): string
        {
            return $this->uid;
        }

        public function getQueue(): string
        {
            return 'craft';
        }
    };

    Event::dispatch(new JobProcessed('sync', $mockJob));

    expect($progressService->getProgress($uid))->toBeNull();
});

it('tracks failed jobs with error message', function () {
    $progressService = app(JobProgressService::class);
    $uid = 'test-cleanup-failed';

    $progressService->setProgress($uid, 'Test Job', 50);

    expect($progressService->getProgress($uid))->not->toBeNull();

    $mockJob = new readonly class($uid)
    {
        public function __construct(private string $uid) {}

        public function uuid(): string
        {
            return $this->uid;
        }

        public function getQueue(): string
        {
            return 'craft';
        }
    };

    Event::dispatch(new JobFailed('sync', $mockJob, new Exception('Test failure')));

    // Failed jobs are kept for display in QueueManager
    $progress = $progressService->getProgress($uid);
    expect($progress)
        ->not->toBeNull()
        ->status->toBe(JobStatus::Failed->value)
        ->error->toBe('Test failure');
});

it('handles jobs without uuid method gracefully', function () {
    $progressService = app(JobProgressService::class);
    $uid = 'test-no-uuid';

    $progressService->setProgress($uid, 'Test Job', 50);

    $mockJob = new class
    {
        public function getQueue(): string
        {
            return 'craft';
        }
    };

    Event::dispatch(new JobProcessed('sync', $mockJob));

    expect($progressService->getProgress($uid))->not->toBeNull();
});

it('handles jobs with null uuid gracefully', function () {
    $progressService = app(JobProgressService::class);
    $uid = 'test-null-uuid';

    $progressService->setProgress($uid, 'Test Job', 50);

    $mockJob = new class
    {
        public function uuid(): ?string
        {
            return null;
        }

        public function getQueue(): string
        {
            return 'craft';
        }
    };

    Event::dispatch(new JobProcessed('sync', $mockJob));

    expect($progressService->getProgress($uid))->not->toBeNull();
});

it('listens for JobProcessed events', function () {
    $listeners = Event::getListeners(JobProcessed::class);

    expect($listeners)->not->toBeEmpty();
});

it('listens for JobFailed events', function () {
    $listeners = Event::getListeners(JobFailed::class);

    expect($listeners)->not->toBeEmpty();
});

it('does not track jobs on non-tracked queues', function () {
    $progressService = app(JobProgressService::class);
    $uid = 'test-non-tracked-queue';

    // Manually set progress to simulate a job that was tracked
    $progressService->setProgress($uid, 'Test Job', 50);

    expect($progressService->getProgress($uid))->not->toBeNull();

    // Create a mock job on the 'default' queue (not tracked by default)
    $mockJob = new readonly class($uid)
    {
        public function __construct(private string $uid) {}

        public function uuid(): string
        {
            return $this->uid;
        }

        public function getQueue(): string
        {
            return 'default';
        }
    };

    // Dispatch event - should not clean up because queue is not tracked
    Event::dispatch(new JobProcessed('sync', $mockJob));

    // Progress should still exist because the queue is not tracked
    expect($progressService->getProgress($uid))->not->toBeNull();
});
