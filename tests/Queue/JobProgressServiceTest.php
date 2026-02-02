<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Queue\Data\ProgressData;
use CraftCms\Cms\Queue\Enums\JobStatus;
use CraftCms\Cms\Queue\JobProgress;
use Illuminate\Contracts\Queue\ClearableQueue;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\freezeTime;

beforeEach(function () {
    $this->service = app(JobProgress::class);
});

it('can set and retrieve job progress', function () {
    $uid = 'test-job-123';

    $this->service->setProgress($uid, 'Test Job', 50, 'Processing item 5 of 10');

    $progress = $this->service->getProgress($uid);

    expect($progress)
        ->toBeInstanceOf(ProgressData::class)
        ->uid->toBe($uid)
        ->description->toBe('Test Job')
        ->progress->toBe(50)
        ->label->toBe('Processing item 5 of 10');
});

it('can update existing job progress', function () {
    $uid = 'test-job-456';

    $this->service->setProgress($uid, 'Test Job', 25, 'Starting');
    $this->service->setProgress($uid, 'Test Job Updated', 75, 'Almost done');

    $progress = $this->service->getProgress($uid);

    expect($progress)
        ->description->toBe('Test Job Updated')
        ->progress->toBe(75)
        ->label->toBe('Almost done');

    // Verify only one row exists
    $count = DB::table(Table::JOBPROGRESS)->where('uid', $uid)->count();
    expect($count)->toBe(1);
});

it('returns null for non-existent job', function () {
    $progress = $this->service->getProgress('non-existent-job');

    expect($progress)->toBeNull();
});

it('can get all active jobs', function () {
    $this->service->setProgress('job-1', 'First Job', 10);
    $this->service->setProgress('job-2', 'Second Job', 50);
    $this->service->setProgress('job-3', 'Third Job', 90);

    $active = $this->service->getActive();

    expect($active)
        ->toHaveCount(3)
        ->pluck('uid')->toContain('job-1', 'job-2', 'job-3');
});

it('returns empty collection when no active jobs', function () {
    $active = $this->service->getActive();

    expect($active)->toBeEmpty();
});

it('can delete a job progress entry', function () {
    $uid = 'job-to-delete';

    $this->service->setProgress($uid, 'Deletable Job', 50);
    expect($this->service->getProgress($uid))->not->toBeNull();

    $this->service->delete($uid);

    expect($this->service->getProgress($uid))->toBeNull();
});

it('can clear all job progress entries', function () {
    $this->service->setProgress('job-a', 'Job A', 10);
    $this->service->setProgress('job-b', 'Job B', 20);
    $this->service->setProgress('job-c', 'Job C', 30);

    expect($this->service->getActive())->toHaveCount(3);

    $this->service->clear();

    expect($this->service->getActive())->toBeEmpty();
});

it('clears the Laravel queue when clearing all job progress', function () {
    Cms::config()->queueName = 'test-queue';
    Cms::config()->lowPriorityQueueName = 'test-low-prio-queue';

    // Create a mock queue connection that implements ClearableQueue
    $queueConnection = Mockery::mock(QueueContract::class, ClearableQueue::class);

    $queueConnection->shouldReceive('clear')->once()->with('test-queue');
    $queueConnection->shouldReceive('clear')->once()->with('test-low-prio-queue');

    Queue::shouldReceive('connection')->once()->andReturn($queueConnection);

    // Add some job progress entries
    $this->service->setProgress('job-a', 'Job A', 10);
    $this->service->setProgress('job-b', 'Job B', 20);

    expect($this->service->getActive())->toHaveCount(2);

    // Clear should remove job progress entries and call clear on the queue
    $this->service->clear();

    expect($this->service->getActive())->toBeEmpty();
});

it('handles progress labels with null value', function () {
    $uid = 'no-label-job';

    $this->service->setProgress($uid, 'No Label Job', 60, null);

    $progress = $this->service->getProgress($uid);

    expect($progress)
        ->progress->toBe(60)
        ->label->toBeNull();
});

it('orders active jobs by creation date', function () {
    // Create jobs with slight delay to ensure ordering
    $this->service->setProgress('first', 'First', 10);
    $this->service->setProgress('second', 'Second', 20);
    $this->service->setProgress('third', 'Third', 30);

    $active = $this->service->getActive();

    expect($active->first()->uid)->toBe('first');
    expect($active->last()->uid)->toBe('third');
});

it('can track a queued job with pending status', function () {
    $uid = 'queued-job-123';

    $this->service->queued($uid, 'Pending Job');

    $progress = $this->service->getProgress($uid);

    expect($progress)
        ->status->toBe(JobStatus::Pending)
        ->description->toBe('Pending Job')
        ->progress->toBe(0);
});

it('can track a delayed job with delayed status', function () {
    $uid = 'delayed-job-123';

    $this->service->queued($uid, 'Delayed Job', delay: 10);

    $progress = $this->service->getProgress($uid);

    expect($progress)
        ->status->toBe(JobStatus::Delayed)
        ->description->toBe('Delayed Job');
});

it('can track a job starting processing with reserved status', function () {
    $uid = 'processing-job-123';

    $this->service->processing($uid);

    $progress = $this->service->getProgress($uid);

    expect($progress)
        ->status->toBe(JobStatus::Reserved)
        ->progress->toBe(0);
});

it('can track a job completing and removes it from tracking', function () {
    $uid = 'completed-job-123';

    $this->service->queued($uid, 'Job to Complete');
    expect($this->service->getProgress($uid))->not->toBeNull();

    $this->service->completed($uid);

    expect($this->service->getProgress($uid))->toBeNull();
});

it('can track a failed job with error message', function () {
    $uid = 'failed-job-123';
    $errorMessage = 'Something went wrong: Database connection failed';

    $this->service->failed($uid, 'Failed Job', $errorMessage);

    $progress = $this->service->getProgress($uid);

    expect($progress)
        ->status->toBe(JobStatus::Failed)
        ->description->toBe('Failed Job')
        ->error->toBe($errorMessage);
});

it('can get jobs by status', function () {
    $this->service->queued('pending-1', 'Pending Job 1');
    $this->service->queued('pending-2', 'Pending Job 2');
    $this->service->processing('reserved-1');
    $this->service->failed('failed-1', 'Failed Job', 'Error');

    $pending = $this->service->getByStatus(JobStatus::Pending);
    $reserved = $this->service->getByStatus(JobStatus::Reserved);
    $failed = $this->service->getByStatus(JobStatus::Failed);

    expect($pending)->toHaveCount(2);
    expect($reserved)->toHaveCount(1);
    expect($failed)->toHaveCount(1);
});

it('can get all failed jobs', function () {
    $this->service->queued('pending-1', 'Pending Job');
    $this->service->failed('failed-1', 'Failed Job 1', 'Error 1');
    $this->service->failed('failed-2', 'Failed Job 2', 'Error 2');

    $failed = $this->service->getFailed();

    expect($failed)
        ->toHaveCount(2)
        ->pluck('uid')->toContain('failed-1', 'failed-2');
});

it('getActive includes pending delayed reserved and not failed jobs', function () {
    $this->service->queued('pending-1', 'Pending Job');
    $this->service->queued('delayed-1', 'Delayed Job', delay: 10);
    $this->service->processing('reserved-1');
    $this->service->failed('failed-1', 'Failed Job', 'Error');

    $active = $this->service->getActive();

    expect($active)
        ->toHaveCount(4)
        ->pluck('uid')->toContain('pending-1', 'delayed-1', 'reserved-1');
});

it('can update job status', function () {
    $uid = 'status-update-job';

    $this->service->queued($uid, 'Job');
    expect($this->service->getProgress($uid)->status)->toBe(JobStatus::Pending);

    $this->service->updateStatus($uid, JobStatus::Reserved);

    // Clear cache to force database read
    Cache::forget('craft_job_progress:'.$uid);
    expect($this->service->getProgress($uid)->status)->toBe(JobStatus::Reserved);
});

it('setProgress sets status to reserved', function () {
    $uid = 'progress-job';

    $this->service->setProgress($uid, 'Progress Job', 50, 'Halfway');

    $progress = $this->service->getProgress($uid);

    expect($progress)
        ->status->toBe(JobStatus::Reserved)
        ->progress->toBe(50);
});

it('clearCompleted only removes done status jobs', function () {
    $this->service->queued('pending-1', 'Pending Job');
    $this->service->failed('failed-1', 'Failed Job', 'Error');

    // Manually set a job to Done status via database
    DB::table(Table::JOBPROGRESS)->insert([
        'uid' => 'done-1',
        'description' => 'Done Job',
        'status' => JobStatus::Done->value,
        'progress' => 100,
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    $this->service->clearCompleted();

    expect($this->service->getProgress('pending-1'))->not->toBeNull();
    expect($this->service->getProgress('failed-1'))->not->toBeNull();
    expect(DB::table(Table::JOBPROGRESS)->where('uid', 'done-1')->exists())->toBeFalse();
});

it('can cancel a job by deleting its progress entry', function () {
    $uid = 'job-to-cancel';

    $this->service->queued($uid, 'Job to Cancel');
    expect($this->service->getProgress($uid))->not->toBeNull();

    $this->service->cancel($uid);

    expect($this->service->getProgress($uid))->toBeNull();
});

it('can check if a job exists', function () {
    $uid = 'existing-job';

    $this->service->queued($uid, 'Existing Job');

    expect($this->service->exists($uid))->toBeTrue();
    expect($this->service->exists('non-existent-job'))->toBeFalse();
});

it('returns false for exists when job was cancelled', function () {
    $uid = 'cancelled-job';

    $this->service->queued($uid, 'Job to Cancel');
    expect($this->service->exists($uid))->toBeTrue();

    $this->service->cancel($uid);
    expect($this->service->exists($uid))->toBeFalse();
});

test('getJobInfo returns sorted job info', function () {
    freezeTime();

    // Failed jobs go last
    $this->service->queued('failed', 'Failed job');
    $this->service->failed('failed');

    // Delayed jobs are third and sorted by their delay
    $this->service->queued('delayed-1', 'Delayed job 1', delay: 10);
    $this->service->queued('delayed-2', 'Delayed job 2', delay: 5);

    // Pending jobs are second
    $this->service->queued('pending', 'Pending job');

    // Reserved jobs are first
    $this->service->queued('processing', 'Processing job');
    $this->service->processing('processing');

    // Completed jobs are removed
    $this->service->queued('completed', 'Completed job');
    $this->service->completed('completed');

    expect($this->service->getJobInfo()->pluck('uid')->toArray())->toBe([
        'processing',
        'pending',
        'delayed-1',
        'delayed-2',
        'failed',
    ]);
});
