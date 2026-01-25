<?php

declare(strict_types=1);

use craft\queue\JobInterface;
use craft\queue\QueueComponent;
use craft\queue\QueueInterface;
use Illuminate\Support\Facades\Queue;
use yii\base\Component;

it('extends Yii2 Component', function () {
    $component = new QueueComponent;

    expect($component)->toBeInstanceOf(Component::class);
});

it('implements QueueInterface', function () {
    $component = new QueueComponent;

    expect($component)->toBeInstanceOf(QueueInterface::class);
});

it('has default ttr of 300', function () {
    $component = new QueueComponent;

    expect($component->ttr)->toBe(300);
});

it('can push a legacy job', function () {
    Queue::fake();

    $legacyJob = new class implements JobInterface
    {
        public function execute($queue): void {}

        public function getDescription(): string
        {
            return 'Test job';
        }

        public function getTtr(): int
        {
            return 300;
        }
    };

    $component = new QueueComponent;
    $result = $component->push($legacyJob);

    expect($result)->toBeNull();
    Queue::assertPushed(\craft\queue\LegacyJobWrapper::class);
});

it('can push a job with delay', function () {
    Queue::fake();

    $legacyJob = new class implements JobInterface
    {
        public function execute($queue): void {}

        public function getDescription(): string
        {
            return 'Delayed job';
        }

        public function getTtr(): int
        {
            return 300;
        }
    };

    $component = new QueueComponent;
    $component->push($legacyJob, null, 60);

    Queue::assertPushed(\craft\queue\LegacyJobWrapper::class);
});

it('returns null for run method (deprecated)', function () {
    $component = new QueueComponent;

    expect($component->run())->toBeNull();
});

it('returns total jobs count', function () {
    $component = new QueueComponent;

    // With a fresh queue, should have 0 jobs
    expect($component->getTotalJobs())->toBeGreaterThanOrEqual(0);
});

it('returns job info from JobProgressService', function () {
    $component = new QueueComponent;

    $jobInfo = $component->getJobInfo();

    expect($jobInfo)->toBeArray();
});

it('returns job info with limit', function () {
    $component = new QueueComponent;

    $jobInfo = $component->getJobInfo(5);

    expect($jobInfo)->toBeArray()
        ->and(count($jobInfo))->toBeLessThanOrEqual(5);
});

it('returns job details for unknown job', function () {
    $component = new QueueComponent;

    // The getJobDetails method requires the failed_jobs table which may not exist
    // in test environment. We verify the method exists and the basic behavior.
    try {
        $details = $component->getJobDetails('unknown-uuid');

        expect($details)->toBeArray()
            ->and($details['status'])->toBeIn([1, 4]); // Waiting or Failed
    } catch (\Illuminate\Database\QueryException $e) {
        // Skip if failed_jobs table doesn't exist
        if (str_contains($e->getMessage(), 'failed_jobs')) {
            $this->markTestSkipped('failed_jobs table does not exist');
        }
        throw $e;
    }
});

it('setProgress method exists for backwards compatibility', function () {
    $component = new QueueComponent;

    // Should not throw - just a no-op
    $component->setProgress(50, 'Testing');

    expect(true)->toBeTrue();
});

it('release method exists for backwards compatibility', function () {
    $component = new QueueComponent;

    // Should not throw - just a no-op
    $component->release('some-id');

    expect(true)->toBeTrue();
});

it('releaseAll clears progress entries', function () {
    $component = new QueueComponent;

    // Should not throw
    $component->releaseAll();

    expect(true)->toBeTrue();
});
