<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\QueueController;
use CraftCms\Cms\Queue\JobProgress;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\action_url;
use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\post;

beforeEach(function () {
    actingAs(User::findOne());
});

function terminatingCallbacks(): array
{
    $property = new ReflectionProperty(Application::class, 'terminatingCallbacks');

    return $property->getValue(app());
}

it('runs the queue anonymously through the site action route', function () {
    Cms::config()->runQueueAutomatically(false);
    Auth::logout();

    get(action_url('queue/run'))->assertOk();
});

it('does not run the queue when automatic queue running is disabled', function () {
    Cms::config()->runQueueAutomatically(false);

    Artisan::shouldReceive('call')->never();

    $callbackCount = count(terminatingCallbacks());

    post(action([QueueController::class, 'run']))
        ->assertOk()
        ->assertContent('');

    expect(terminatingCallbacks())->toHaveCount($callbackCount);
});

it('does not run the queue when a job is reserved', function () {
    Cms::config()->runQueueAutomatically(true);

    app(JobProgress::class)->processing('reserved-job');
    Artisan::shouldReceive('call')->never();

    $callbackCount = count(terminatingCallbacks());

    post(action([QueueController::class, 'run']))
        ->assertOk()
        ->assertContent('');

    expect(terminatingCallbacks())->toHaveCount($callbackCount);
});

it('does not run the queue when there are no pending jobs', function () {
    Cms::config()->runQueueAutomatically(true);

    Artisan::shouldReceive('call')->never();

    $callbackCount = count(terminatingCallbacks());

    post(action([QueueController::class, 'run']))
        ->assertOk()
        ->assertContent('');

    expect(terminatingCallbacks())->toHaveCount($callbackCount);
});

it('runs one queued job after the response terminates', function () {
    Cms::config()
        ->runQueueAutomatically(true)
        ->queueName('high')
        ->lowPriorityQueueName('low')
        ->phpMaxMemoryLimit('512M');

    app(JobProgress::class)->queued('pending-job', 'Pending Job');
    Artisan::shouldReceive('call')->once()->with('queue:work', [
        '--queue' => 'high,low',
        '--memory' => '512M',
        '--once',
    ])->andReturn(0);

    $callbackCount = count(terminatingCallbacks());

    post(action([QueueController::class, 'run']))
        ->assertOk()
        ->assertContent('');

    expect(terminatingCallbacks())->toHaveCount($callbackCount + 1);
});

it('does not run the queue during maintenance mode', function () {
    Cms::config()->runQueueAutomatically(true);
    app(JobProgress::class)->queued('pending-job', 'Pending Job');
    app()->maintenanceMode()->activate([]);
    Artisan::shouldReceive('call')->never();

    $callbackCount = count(terminatingCallbacks());

    post(cp_url(Cms::config()->actionTrigger.'/queue/run'))
        ->assertOk()
        ->assertContent('');

    expect(terminatingCallbacks())->toHaveCount($callbackCount);
});

it('deduplicates queue names and falls back to the default memory limit', function () {
    Cms::config()
        ->runQueueAutomatically(true)
        ->queueName('default')
        ->lowPriorityQueueName('default')
        ->phpMaxMemoryLimit('');

    app(JobProgress::class)->queued('pending-job', 'Pending Job');
    Artisan::shouldReceive('call')->once()->with('queue:work', [
        '--queue' => 'default',
        '--memory' => '1536M',
        '--once',
    ])->andReturn(0);

    post(action([QueueController::class, 'run']))->assertOk();
});

it('returns total jobs and job info without a limit', function () {
    app(JobProgress::class)->queued('job-1', 'First Job');
    app(JobProgress::class)->queued('job-2', 'Second Job');

    getJson(action([QueueController::class, 'jobInfo']))
        ->assertOk()
        ->assertJsonPath('total', 2)
        ->assertJsonCount(2, 'jobs')
        ->assertJsonPath('jobs.0.uid', 'job-1')
        ->assertJsonPath('jobs.1.uid', 'job-2');
});

it('does not require Queue Manager utility access for job info', function () {
    $utilities = Mockery::mock(Utilities::class);
    $utilities->shouldNotReceive('checkAuthorization');
    app()->instance(Utilities::class, $utilities);

    getJson(action([QueueController::class, 'jobInfo']))->assertOk();
});

it('requires authentication for job info', function () {
    Auth::logout();

    getJson(action([QueueController::class, 'jobInfo']))->assertUnauthorized();
});

it('passes the requested limit to job info', function () {
    app(JobProgress::class)->queued('job-1', 'First Job');
    app(JobProgress::class)->queued('job-2', 'Second Job');
    app(JobProgress::class)->queued('job-3', 'Third Job');

    getJson(action([QueueController::class, 'jobInfo'], ['limit' => '1']))
        ->assertOk()
        ->assertJsonPath('total', 3)
        ->assertJsonCount(1, 'jobs')
        ->assertJsonPath('jobs.0.uid', 'job-1');
});

it('treats a zero job info limit as unlimited', function () {
    app(JobProgress::class)->queued('job-1', 'First Job');
    app(JobProgress::class)->queued('job-2', 'Second Job');

    getJson(action([QueueController::class, 'jobInfo'], ['limit' => '0']))
        ->assertOk()
        ->assertJsonPath('total', 2)
        ->assertJsonCount(2, 'jobs');
});

it('cancels a job', function () {
    app(JobProgress::class)->queued('job-1', 'Pending Job');

    post(action([QueueController::class, 'cancel'], ['job-1']))
        ->assertRedirectBack();

    expect(app(JobProgress::class)->getProgress('job-1'))->toBeNull();
});

it('cancels all jobs', function () {
    app(JobProgress::class)->queued('job-1', 'First Job');
    app(JobProgress::class)->queued('job-2', 'Second Job');

    post(action([QueueController::class, 'cancelAll']))
        ->assertRedirectBack();

    expect(app(JobProgress::class)->getTotalJobs())->toBe(0);
});

it('retries a job and returns the queue runner response', function () {
    Cms::config()->runQueueAutomatically(false);

    Artisan::shouldReceive('call')->once()->with('queue:retry', [
        'id' => 'job-1',
    ])->andReturn(0);

    post(action([QueueController::class, 'retry'], ['job-1']))
        ->assertRedirectBack();
});

it('retries all jobs and returns the queue runner response', function () {
    Cms::config()->runQueueAutomatically(false);

    Artisan::shouldReceive('call')->once()->with('queue:retry', [
        'id' => 'all',
    ])->andReturn(0);

    post(action([QueueController::class, 'retryAll']))
        ->assertRedirectBack();
});
