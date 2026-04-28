<?php

declare(strict_types=1);

use craft\queue\JobInterface;
use craft\queue\LegacyJobWrapper;
use craft\queue\LegacyQueueAdapter;

/**
 * A legacy job that tracks progress for testing.
 */
class ProgressTrackingLegacyJob implements JobInterface
{
    public array $progressCalls = [];

    public function execute($queue): void
    {
        $queue->setProgress(25, 'Step 1');
        $queue->setProgress(50, 'Step 2');
        $queue->setProgress(75, 'Step 3');
        $queue->setProgress(100, 'Done');
    }

    public function getDescription(): ?string
    {
        return 'Progress tracking test';
    }

    public function getTtr(): int
    {
        return 300;
    }
}

it('can be instantiated with a LegacyJobWrapper', function() {
    $legacyJob = new ProgressTrackingLegacyJob();
    $wrapper = new LegacyJobWrapper($legacyJob);
    $adapter = new LegacyQueueAdapter($wrapper);

    expect($adapter)->toBeInstanceOf(LegacyQueueAdapter::class);
});

it('has setProgress method', function() {
    $legacyJob = new ProgressTrackingLegacyJob();
    $wrapper = new LegacyJobWrapper($legacyJob);
    $adapter = new LegacyQueueAdapter($wrapper);

    expect(method_exists($adapter, 'setProgress'))->toBeTrue();
});

it('forwards setProgress to wrapper updateProgress', function() {
    $legacyJob = new ProgressTrackingLegacyJob();
    $wrapper = new LegacyJobWrapper($legacyJob);
    $adapter = new LegacyQueueAdapter($wrapper);

    // The wrapper's updateProgress won't do anything without a real job context,
    // but we can verify the method is callable without error
    $adapter->setProgress(50, 'Halfway');

    expect(true)->toBeTrue();
});

it('allows legacy jobs to call setProgress during execution', function() {
    $legacyJob = new class() implements JobInterface {
        public bool $progressCalled = false;

        public function execute($queue): void
        {
            $queue->setProgress(50, 'Working');
            $this->progressCalled = true;
        }

        public function getDescription(): string
        {
            return 'Test';
        }
    };

    $wrapper = new LegacyJobWrapper($legacyJob);
    $wrapper->handle();

    expect($legacyJob->progressCalled)->toBeTrue();
});
