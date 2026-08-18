<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Queue\Enums\JobStatus;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Queue\JobProgress;
use CraftCms\Cms\Queue\QueueServiceProvider;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;

it('keeps retry windows safely above Craft job timeouts', function () {
    config()->set('queue.connections', [
        'database' => ['driver' => 'database', 'retry_after' => 90],
        'redis' => ['driver' => 'redis', 'retry_after' => 600],
        'sqs' => ['driver' => 'sqs'],
    ]);

    app()->register(QueueServiceProvider::class, force: true);

    expect(config('queue.connections.database.retry_after'))->toBe(360)
        ->and(config('queue.connections.redis.retry_after'))->toBe(600)
        ->and(config('queue.connections.sqs'))->not->toHaveKey('retry_after');
});

it('registers JobProgressService as singleton', function () {
    $service1 = app(JobProgress::class);
    $service2 = app(JobProgress::class);

    expect($service1)->toBeInstanceOf(JobProgress::class)
        ->and($service1)->toBe($service2);
});

it('is registered in the application', function () {
    $providers = collect(app()->getLoadedProviders())
        ->keys()
        ->all();

    expect($providers)->toContain(QueueServiceProvider::class);
});

it('tracks failed jobs with error message', function () {
    $progressService = app(JobProgress::class);
    $uid = 'test-cleanup-failed';

    $progressService->setProgress($uid, 'Test Job', 50);

    expect($progressService->getProgress($uid))->not->toBeNull();

    $mockJob = new readonly class($uid)
    {
        public function __construct(private string $uid) {}

        public function payload(): array
        {
            return ['uuid' => $this->uid];
        }

        public function getQueue(): string
        {
            return Cms::config()->queueName;
        }
    };

    Event::dispatch(new JobFailed('sync', $mockJob, new Exception('Test failure')));

    // Failed jobs are kept for display in QueueManager
    $progress = $progressService->getProgress($uid);
    expect($progress)
        ->not->toBeNull()
        ->status->toBe(JobStatus::Failed)
        ->error->toBe('Test failure');
});

it('marks sync jobs as completed', function () {
    dispatch(new SyncProgressJob);

    $progress = app(JobProgress::class)->getAll()->firstWhere('description', SyncProgressJob::class);

    expect($progress?->status)->toBe(JobStatus::Done);
});

it('handles jobs without uuid method gracefully', function () {
    $progressService = app(JobProgress::class);
    $uid = 'test-no-uuid';

    $progressService->setProgress($uid, 'Test Job', 50);

    $mockJob = new class
    {
        public function getQueue(): string
        {
            return Cms::config()->queueName;
        }

        public function payload(): array
        {
            return [];
        }
    };

    Event::dispatch(new JobProcessed('sync', $mockJob));

    expect($progressService->getProgress($uid))->not->toBeNull();
});

it('handles jobs with null uuid gracefully', function () {
    $progressService = app(JobProgress::class);
    $uid = 'test-null-uuid';

    $progressService->setProgress($uid, 'Test Job', 50);

    $mockJob = new class
    {
        public function payload(): array
        {
            return ['uuid' => null];
        }

        public function getQueue(): string
        {
            return Cms::config()->queueName;
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
    $progressService = app(JobProgress::class);
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
            return 'non-tracked';
        }
    };

    // Dispatch event - should not clean up because queue is not tracked
    Event::dispatch(new JobProcessed('sync', $mockJob));

    // Progress should still exist because the queue is not tracked
    expect($progressService->getProgress($uid))->not->toBeNull();
});

class SyncProgressJob extends Job
{
    public function handle(): void
    {
        $this->setProgress(50);
    }
}
