<?php

declare(strict_types=1);

use craft\queue\JobInterface;
use craft\queue\LegacyJobWrapper;
use craft\queue\QueueComponent as Queue;
use CraftCms\Cms\Queue\Contracts\DescribableJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Queue as QueueFacade;

/**
 * A simple legacy job implementation for testing.
 */
class TestLegacyJob implements JobInterface
{
    public bool $executed = false;

    public function __construct(public ?string $description = null)
    {
    }

    public function execute($queue): void
    {
        $this->executed = true;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getTtr(): int
    {
        return 600;
    }
}

it('implements ShouldQueue interface', function() {
    $legacyJob = new TestLegacyJob('Test description');
    $wrapper = new LegacyJobWrapper($legacyJob);

    expect($wrapper)->toBeInstanceOf(ShouldQueue::class);
});

it('implements DescribableJob interface', function() {
    $legacyJob = new TestLegacyJob('Test description');
    $wrapper = new LegacyJobWrapper($legacyJob);

    expect($wrapper)->toBeInstanceOf(DescribableJob::class);
});

it('wraps a legacy job', function() {
    $legacyJob = new TestLegacyJob('Test description');
    $wrapper = new LegacyJobWrapper($legacyJob);

    expect($wrapper->getLegacyJob())->toBe($legacyJob);
});

it('uses TTR from legacy job as timeout', function() {
    $legacyJob = new TestLegacyJob();
    $wrapper = new LegacyJobWrapper($legacyJob);

    expect($wrapper->timeout)->toBe(600);
});

it('defaults to 300 second timeout when legacy job has no getTtr', function() {
    $legacyJob = new class() implements JobInterface {
        public function execute($queue): void
        {
        }

        public function getDescription(): ?string
        {
            return null;
        }
    };

    $wrapper = new LegacyJobWrapper($legacyJob);

    expect($wrapper->timeout)->toBe(300);
});

it('returns description from legacy job', function() {
    $legacyJob = new TestLegacyJob('My custom description');
    $wrapper = new LegacyJobWrapper($legacyJob);

    expect($wrapper->getDescription())->toBe('My custom description');
});

it('returns class name when legacy job has no description', function() {
    $legacyJob = new TestLegacyJob();
    $wrapper = new LegacyJobWrapper($legacyJob);

    expect($wrapper->getDescription())->toBe(TestLegacyJob::class);
});

it('can be dispatched to the queue', function() {
    QueueFacade::fake();

    $legacyJob = new TestLegacyJob('Test job');
    $wrapper = new LegacyJobWrapper($legacyJob);

    dispatch($wrapper);

    QueueFacade::assertPushed(LegacyJobWrapper::class);
});

it('executes the legacy job via LegacyQueueAdapter', function() {
    $legacyJob = new class() implements JobInterface {
        public bool $executed = false;

        public mixed $receivedQueue = null;

        public function execute($queue): void
        {
            $this->executed = true;
            $this->receivedQueue = $queue;
        }

        public function getDescription(): string
        {
            return 'Test';
        }
    };

    $wrapper = new LegacyJobWrapper($legacyJob);
    $wrapper->handle();

    expect($legacyJob->executed)->toBeTrue()
        ->and($legacyJob->receivedQueue)->toBeInstanceOf(Queue::class);
});
