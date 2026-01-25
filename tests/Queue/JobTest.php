<?php

declare(strict_types=1);

use CraftCms\Cms\Queue\Contracts\DescribableJob;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Queue\JobProgress;
use CraftCms\Cms\Queue\Middleware\ShouldRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Queue;

it('implements ShouldQueue interface', function () {
    $job = new class extends Job
    {
        public function handle(): void {}
    };

    expect($job)->toBeInstanceOf(ShouldQueue::class);
});

it('implements DescribableJob interface', function () {
    $job = new class extends Job
    {
        public function handle(): void {}
    };

    expect($job)->toBeInstanceOf(DescribableJob::class);
});

it('has default timeout of 300 seconds', function () {
    $job = new class extends Job
    {
        public function handle(): void {}
    };

    expect($job->timeout)->toBe(300);
});

it('sets queue name from config', function () {
    $job = new class extends Job
    {
        public function handle(): void {}
    };

    expect($job->queue)->toBe('craft');
});

it('can set a custom timeout', function () {
    $job = new class extends Job
    {
        public int $timeout = 600;

        public function handle(): void {}
    };

    expect($job->timeout)->toBe(600);
});

it('returns class name as default description', function () {
    $job = new class extends Job
    {
        public function handle(): void {}
    };

    expect($job->getDescription())->toContain('Queue\\Job@anonymous');
});

it('returns custom description when set', function () {
    $job = new class extends Job
    {
        protected ?string $description = 'My Custom Job';

        public function handle(): void {}
    };

    expect($job->getDescription())->toBe('My Custom Job');
});

it('can override defaultDescription method', function () {
    $job = new class extends Job
    {
        public function handle(): void {}

        protected function defaultDescription(): string
        {
            return 'Custom Default Description';
        }
    };

    expect($job->getDescription())->toBe('Custom Default Description');
});

it('custom description takes precedence over defaultDescription', function () {
    $job = new class extends Job
    {
        protected ?string $description = 'Explicit Description';

        public function handle(): void {}

        protected function defaultDescription(): string
        {
            return 'Default Description';
        }
    };

    expect($job->getDescription())->toBe('Explicit Description');
});

it('can be dispatched to the queue', function () {
    Queue::fake();

    $job = new class extends Job
    {
        public function handle(): void {}
    };

    dispatch($job);

    Queue::assertPushed($job::class);
});

it('can be dispatched with delay', function () {
    Queue::fake();

    $job = new class extends Job
    {
        public function handle(): void {}
    };

    dispatch($job)->delay(60);

    Queue::assertPushed($job::class, fn ($pushedJob, $queue, $delay) => $delay !== null);
});

it('returns middleware array with CheckShouldRun', function () {
    $job = new class extends Job
    {
        public function handle(): void {}
    };

    $middleware = $job->middleware();

    expect($middleware)->toBeArray()
        ->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(ShouldRun::class);
});

it('returns true from shouldStillRun when progress exists', function () {
    $job = new class extends Job
    {
        public function handle(): void {}
    };

    // Create a mock job object with uuid
    $mockQueueJob = new class
    {
        public function uuid(): string
        {
            return 'test-job-uuid';
        }
    };

    // Set the job property via reflection
    $reflection = new ReflectionProperty($job, 'job');
    $reflection->setValue($job, $mockQueueJob);

    // Track the job so it exists
    app(JobProgress::class)->queued('test-job-uuid', 'Test Job');

    expect($job->shouldStillRun())->toBeTrue();
});

it('returns false from shouldStillRun when progress is deleted', function () {
    $job = new class extends Job
    {
        public function handle(): void {}
    };

    // Create a mock job object with uuid
    $mockQueueJob = new class
    {
        public function uuid(): string
        {
            return 'cancelled-job-uuid';
        }
    };

    // Set the job property via reflection
    $reflection = new ReflectionProperty($job, 'job');
    $reflection->setValue($job, $mockQueueJob);

    // Don't track the job - simulates it being cancelled/deleted
    // so shouldStillRun should return false

    expect($job->shouldStillRun())->toBeFalse();
});

it('returns true from shouldStillRun when no uuid available', function () {
    $job = new class extends Job
    {
        public function handle(): void {}
    };

    // No job property set, so uuid() will return null
    expect($job->shouldStillRun())->toBeTrue();
});
