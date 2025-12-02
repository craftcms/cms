<?php

declare(strict_types=1);

use CraftCms\Cms\Deprecator\Deprecator;
use CraftCms\Cms\Deprecator\Models\DeprecationError;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    /**
     * Reset initial state
     */
    Deprecator::$logTarget = 'db';
    Deprecator::$throwExceptions = false;

    $this->deprecator = resolve(Deprecator::class);
});

it('has a working facade', function () {
    expect(\CraftCms\Cms\Support\Facades\Deprecator::getTotalLogs())->toBe(0);
});

it('can log deprecations to the log', function () {
    Deprecator::$logTarget = 'logs';

    expect(DeprecationError::count())->toBe(0);

    $this->deprecator->log('foo', 'A deprecation message');
    $this->deprecator->storeLogs();

    expect(DeprecationError::count())->toBe(0);
    expect(File::get(storage_path('logs/laravel.log')))->toContain('A deprecation message');
});

it('can log deprecations to the database', function () {
    expect(DeprecationError::count())->toBe(0);

    $this->deprecator->log('foo', 'A deprecation message');

    expect(DeprecationError::count())->toBe(0);

    $this->deprecator->storeLogs();

    expect(DeprecationError::count())->toBe(1);
    tap(DeprecationError::query()->firstOrFail(), function (DeprecationError $error) {
        expect($error->key)->toBe('foo');
        expect($error->traces)->toBeArray();
        expect($error->uid)->not()->toBe('0');
    });
});

it('can get the logs in the current request', function () {
    expect($this->deprecator->getRequestLogs())->toBeEmpty();

    $this->deprecator->log('foo', 'A deprecation message');

    expect($this->deprecator->getRequestLogs())->toHaveCount(1);
});

it('can get the total log count', function () {
    expect($this->deprecator->getTotalLogs())->toBe(0);

    $this->deprecator->log('foo', 'A deprecation message');

    expect($this->deprecator->getTotalLogs())->toBe(0);

    $this->deprecator->storeLogs();

    expect($this->deprecator->getTotalLogs())->toBe(1);
});

it('can get all logs', function () {
    expect(DeprecationError::count())->toBe(0);

    $this->deprecator->log('foo', 'A deprecation message');

    expect(DeprecationError::count())->toBe(0);

    $this->deprecator->storeLogs();

    expect($this->deprecator->getLogs())->toHaveCount(1);
});

it('can get a log by id', function () {
    expect($this->deprecator->getLogById(1))->toBeNull();

    $this->deprecator->log('foo', 'A deprecation message');
    $this->deprecator->storeLogs();

    $first = DeprecationError::firstOrFail();

    expect($this->deprecator->getLogById($first->id)?->is($first))->toBeTrue();
});

it('can delete a log by id', function () {
    $this->deprecator->log('foo', 'A deprecation message');
    $this->deprecator->storeLogs();

    $first = DeprecationError::firstOrFail();

    $this->deprecator->deleteLogById($first->id);

    expect(DeprecationError::count())->toBe(0);
});

it('can delete all logs', function () {
    $this->deprecator->log('foo', 'A deprecation message');
    $this->deprecator->log('foo2', 'A deprecation message');
    $this->deprecator->storeLogs();

    expect(DeprecationError::count())->toBe(2);

    $this->deprecator->deleteAllLogs();

    expect(DeprecationError::count())->toBe(0);
});
