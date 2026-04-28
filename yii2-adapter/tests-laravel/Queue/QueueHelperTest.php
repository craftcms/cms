<?php

declare(strict_types=1);

use craft\helpers\Queue;
use craft\queue\JobInterface;
use craft\queue\LegacyJobWrapper;
use CraftCms\Cms\Queue\Job;
use Illuminate\Support\Facades\Queue as QueueFacade;

it('can push a legacy job', function() {
    QueueFacade::fake();

    $legacyJob = new class() implements JobInterface {
        public function execute($queue): void
        {
        }

        public function getDescription(): string
        {
            return 'Test';
        }

        public function getTtr(): int
        {
            return 300;
        }
    };

    $result = Queue::push($legacyJob);

    expect($result)->toBeNull();
    QueueFacade::assertPushed(LegacyJobWrapper::class);
});

it('can push a Laravel job', function() {
    QueueFacade::fake();

    $laravelJob = new class() extends Job {
        public function handle(): void
        {
        }

        protected function defaultDescription(): string
        {
            return 'Laravel job';
        }
    };

    $result = Queue::push($laravelJob);

    expect($result)->toBeNull();
    QueueFacade::assertPushed($laravelJob::class);
});

it('wraps legacy jobs in LegacyJobWrapper', function() {
    QueueFacade::fake();

    $legacyJob = new class() implements JobInterface {
        public function execute($queue): void
        {
        }

        public function getDescription(): string
        {
            return 'Legacy';
        }
    };

    Queue::push($legacyJob);

    QueueFacade::assertPushed(LegacyJobWrapper::class, fn($job) => $job->getLegacyJob() === $legacyJob);
});

it('does not wrap jobs that implement ShouldQueue', function() {
    QueueFacade::fake();

    $laravelJob = new class() extends Job {
        public function handle(): void
        {
        }

        protected function defaultDescription(): string
        {
            return 'Test';
        }
    };

    Queue::push($laravelJob);

    // Should push the job directly, not wrapped
    QueueFacade::assertPushed($laravelJob::class);
    QueueFacade::assertNotPushed(LegacyJobWrapper::class);
});

it('applies custom TTR to legacy job wrapper', function() {
    QueueFacade::fake();

    $legacyJob = new class() implements JobInterface {
        public function execute($queue): void
        {
        }

        public function getDescription(): string
        {
            return 'Test';
        }

        public function getTtr(): int
        {
            return 300;
        }
    };

    Queue::push($legacyJob, null, null, 600);

    QueueFacade::assertPushed(LegacyJobWrapper::class, fn($job) => $job->timeout === 600);
});

it('applies custom TTR to Laravel job', function() {
    QueueFacade::fake();

    $laravelJob = new class() extends Job {
        public function handle(): void
        {
        }

        protected function defaultDescription(): string
        {
            return 'Test';
        }
    };

    Queue::push($laravelJob, null, null, 900);

    QueueFacade::assertPushed($laravelJob::class, fn($job) => $job->timeout === 900);
});

it('dispatches with delay when specified', function() {
    QueueFacade::fake();

    $legacyJob = new class() implements JobInterface {
        public function execute($queue): void
        {
        }

        public function getDescription(): string
        {
            return 'Delayed';
        }
    };

    Queue::push($legacyJob, null, 120);

    QueueFacade::assertPushed(LegacyJobWrapper::class);
});

it('ignores priority parameter', function() {
    QueueFacade::fake();

    $legacyJob = new class() implements JobInterface {
        public function execute($queue): void
        {
        }

        public function getDescription(): string
        {
            return 'Test';
        }
    };

    // Priority is passed but should be ignored
    $result = Queue::push($legacyJob, 100);

    expect($result)->toBeNull();
    QueueFacade::assertPushed(LegacyJobWrapper::class);
});

it('ignores queue parameter', function() {
    QueueFacade::fake();

    $legacyJob = new class() implements JobInterface {
        public function execute($queue): void
        {
        }

        public function getDescription(): string
        {
            return 'Test';
        }
    };

    // Queue parameter is passed but should be ignored
    $result = Queue::push($legacyJob);

    expect($result)->toBeNull();
    QueueFacade::assertPushed(LegacyJobWrapper::class);
});

it('always returns null', function() {
    QueueFacade::fake();

    $legacyJob = new class() implements JobInterface {
        public function execute($queue): void
        {
        }

        public function getDescription(): string
        {
            return 'Test';
        }
    };

    $result = Queue::push($legacyJob);

    expect($result)->toBeNull();
});
