<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Queue\QueueWorkerHeartbeat;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\Date;

afterEach(function () {
    Date::setTestNow();
});

it('reports a worker as running until three minutes after it last polled the queue', function () {
    expect(app(QueueWorkerHeartbeat::class)->isRunning())->toBeFalse();

    Date::setTestNow('2026-10-01 12:00:00');
    $this->artisan('queue:work', ['--stop-when-empty' => true, '--memory' => 4096])->assertSuccessful();

    Date::setTestNow('2026-10-01 12:02:59');
    expect(app(QueueWorkerHeartbeat::class)->isRunning())->toBeTrue();

    Date::setTestNow('2026-10-01 12:03:01');
    expect(app(QueueWorkerHeartbeat::class)->isRunning())->toBeFalse();
});

it('does not count a worker run with --once, as the browser queue runner does', function () {
    $this->artisan('queue:work', ['--once' => true, '--memory' => 4096])->assertSuccessful();

    expect(app(QueueWorkerHeartbeat::class)->isRunning())->toBeFalse();
});

it('only counts workers that poll both of Craft’s queues on the default connection', function () {
    Cms::config()->queueName('high')->lowPriorityQueueName('low');

    event(new Looping(config('queue.default'), 'emails'));
    event(new Looping('other', 'high,low'));
    event(new Looping(config('queue.default'), 'high'));
    expect(app(QueueWorkerHeartbeat::class)->isRunning())->toBeFalse();

    event(new Looping(config('queue.default'), 'emails,low'));
    expect(app(QueueWorkerHeartbeat::class)->isRunning())->toBeTrue();
});
