<?php

declare(strict_types=1);

use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Queue\Middleware\CheckShouldRun;

it('allows job to continue when shouldStillRun returns true', function () {
    $middleware = new CheckShouldRun;

    $job = new class extends Job
    {
        public bool $handled = false;

        public function handle(): void
        {
            $this->handled = true;
        }

        public function shouldStillRun(): bool
        {
            return true;
        }
    };

    $nextCalled = false;

    $middleware->handle($job, function () use (&$nextCalled) {
        $nextCalled = true;
    });

    expect($nextCalled)->toBeTrue();
});

it('deletes job and stops execution when shouldStillRun returns false', function () {
    $middleware = new CheckShouldRun;

    $mockQueueJob = new class
    {
        public bool $deleted = false;

        public function delete(): void
        {
            $this->deleted = true;
        }

        public function uuid(): string
        {
            return 'test-uuid';
        }
    };

    $job = new class extends Job
    {
        public function handle(): void {}

        public function shouldStillRun(): bool
        {
            return false;
        }
    };

    // Set the job property via reflection
    $reflection = new ReflectionProperty($job, 'job');
    $reflection->setValue($job, $mockQueueJob);

    $nextCalled = false;

    $middleware->handle($job, function () use (&$nextCalled) {
        $nextCalled = true;
    });

    expect($nextCalled)->toBeFalse();
    expect($mockQueueJob->deleted)->toBeTrue();
});

it('passes through non-Job instances', function () {
    $middleware = new CheckShouldRun;

    $nonJob = new stdClass;
    $nonJob->data = 'test';

    $nextCalled = false;
    $passedJob = null;

    $middleware->handle($nonJob, function ($job) use (&$nextCalled, &$passedJob) {
        $nextCalled = true;
        $passedJob = $job;
    });

    expect($nextCalled)->toBeTrue();
});

it('handles job with null uuid gracefully', function () {
    $middleware = new CheckShouldRun;

    $mockQueueJob = new class
    {
        public function uuid(): ?string
        {
            return null;
        }
    };

    $job = new class extends Job
    {
        public function handle(): void {}
    };

    // Set the job property via reflection
    $reflection = new ReflectionProperty($job, 'job');
    $reflection->setValue($job, $mockQueueJob);

    $nextCalled = false;

    $middleware->handle($job, function () use (&$nextCalled) {
        $nextCalled = true;
    });

    // Should continue because shouldStillRun returns true when no uuid
    expect($nextCalled)->toBeTrue();
});
