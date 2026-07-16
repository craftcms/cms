<?php

declare(strict_types=1);

use CraftCms\Cms\ProjectConfig\Data\ReadOnlyProjectConfigData;
use CraftCms\Cms\ProjectConfig\Events\ItemAdded;
use CraftCms\Cms\ProjectConfig\Events\ItemRemoved;
use CraftCms\Cms\ProjectConfig\Events\ItemUpdated;
use CraftCms\Cms\ProjectConfig\Exceptions\ReadonlyException;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Cache::lock(ProjectConfig::MUTEX_NAME)->forceRelease();
});

afterEach(function () {
    Cache::lock(ProjectConfig::MUTEX_NAME)->forceRelease();
    Date::setTestNow();
});

function getFakeProjectConfig(?array $internal = null, ?array $external = null): ProjectConfig
{
    app()->forgetInstance(ProjectConfig::class);

    $projectConfig = app(ProjectConfig::class);

    $reflectionClass = new ReflectionClass($projectConfig);
    $reflectionClass->getProperty('_internalConfig')->setValue($projectConfig, new ReadOnlyProjectConfigData($internal ?? [
        'a' => 'b',
        'b' => [
            'c' => 'd',
        ],
        'e' => [1, 2, 3],
        'f' => 'g',
        'randomString' => 'Entirely random',
        'dateModified' => 1609452000,
    ], $projectConfig));

    $reflectionClass->getProperty('_externalConfig')->setValue($projectConfig, new ReadOnlyProjectConfigData($external ?? [
        'aa' => 'bb',
        'bb' => [
            'vc' => 'dd',
        ],
        'ee' => [11, 22, 33],
        'f' => 'g',
    ], $projectConfig));

    return $projectConfig;
}

it('does not release a lock acquired by another process', function () {
    $projectConfig = getFakeProjectConfig();
    $projectConfig->set('a', 'changed');

    Date::setTestNow(now()->addSeconds(31));
    $newLock = Cache::lock(ProjectConfig::MUTEX_NAME, 30);

    expect($newLock->get())->toBeTrue();

    $projectConfig->saveModifiedConfigData();

    expect(Cache::lock(ProjectConfig::MUTEX_NAME)->get())->toBeFalse();

    $newLock->release();
});

test('rebuild ignores readonly', function () {
    $projectConfig = app(ProjectConfig::class);
    $readOnly = $projectConfig->readOnly;
    $projectConfig->readOnly = true;

    // Must trigger exception
    $thrown = false;
    try {
        $projectConfig->set('oops', true);
    } catch (ReadonlyException) {
        $thrown = true;
    }
    expect($thrown)->toBeTrue();

    // Must not trigger exception
    $projectConfig->rebuild();

    $projectConfig->readOnly = $readOnly;
});

test('get value', function (?string $path, bool $useExternal, mixed $expectedValue) {
    $actualValue = getFakeProjectConfig()->get($path, $useExternal);

    expect($actualValue)->toEqual($expectedValue);
})->with([
    ['a', false, 'b'],
    ['aa', false, null],
    ['aa', true, 'bb'],
    ['b', false, ['c' => 'd']],
    ['b.c', false, 'd'],
    ['ee.1', true, 22],
    ['ee', true, [11, 22, 33]],
    [null, true, [
        'aa' => 'bb',
        'bb' => [
            'vc' => 'dd',
        ],
        'ee' => [11, 22, 33],
        'f' => 'g',
    ]],
]);

test('set value', function (string $path, mixed $value) {
    $projectConfig = getFakeProjectConfig();
    $projectConfig->set($path, $value);

    $actual = $projectConfig->get($path);

    expect($actual)->toEqual($value);
})->with([
    ['a', 'bar'],
    ['x', ['a' => 'b']],
    ['f', null],
]);

test('setting value modifies timestamp', function () {
    $projectConfig = getFakeProjectConfig();
    $path = 'randomString';
    $initialValue = $projectConfig->get($path);
    $initialTimestamp = $projectConfig->get('dateModified');

    $projectConfig->set($path, $initialValue);
    expect($projectConfig->get('dateModified'))->toBe($initialTimestamp);

    $projectConfig->set($path, Str::random());
    expect($projectConfig->get('dateModified'))->toBeGreaterThan($initialTimestamp);
});

test('setting value ignores external value', function () {
    $internal = [
        'common' => [
            'foo' => 'bar',
            'bar' => 'baz',
        ],
    ];

    $external = [
        'common' => [
            'box' => 'bax',
        ],
    ];
    $pc = getFakeProjectConfig($internal, $external);
    $pc->set('common.fizz', 'buzz');

    // Expect project config to have the merged value
    expect($pc->get('common.fizz'))->toBe('buzz');
    expect($pc->get('common.foo'))->toBe('bar');

    // Expect the external storage to be unaware of anything
    expect($pc->get('common.box', true))->toBe('bax');
    expect($pc->get('common.fizz', true))->toBe(null);
});

it('prevents changes if readonly', function () {
    $pc = getFakeProjectConfig();
    $pc->readOnly = true;
    $this->expectExceptionMessage('while in read-only');
    $pc->set('path', 'value');
});

it('fires events', function () {
    Event::fake();

    $pc = getFakeProjectConfig();

    $pc->set('some.path', 'value');
    $pc->saveModifiedConfigData();

    $pc->remove('some.path');
    $pc->saveModifiedConfigData();

    Event::assertDispatched(ItemAdded::class);
    Event::assertDispatched(ItemUpdated::class);
    Event::assertDispatched(ItemRemoved::class);
});
