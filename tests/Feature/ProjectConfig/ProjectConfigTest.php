<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\Events\ChangesApplied;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\Events\ItemAdded;
use CraftCms\Cms\ProjectConfig\Events\ItemRemoved;
use CraftCms\Cms\ProjectConfig\Events\ItemUpdated;
use CraftCms\Cms\ProjectConfig\Events\YamlFilesWritten;
use CraftCms\Cms\ProjectConfig\Exceptions\BusyResourceException;
use CraftCms\Cms\ProjectConfig\Exceptions\ReadonlyException;
use CraftCms\Cms\ProjectConfig\Exceptions\StaleResourceException;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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
    $reflectionClass->getProperty('current')->setValue($projectConfig, $internal ?? [
        'a' => 'b',
        'b' => [
            'c' => 'd',
        ],
        'e' => [1, 2, 3],
        'f' => 'g',
        'randomString' => 'Entirely random',
        'dateModified' => 1609452000,
    ]);

    $reflectionClass->getProperty('external')->setValue($projectConfig, $external ?? [
        'aa' => 'bb',
        'bb' => [
            'vc' => 'dd',
        ],
        'ee' => [11, 22, 33],
        'f' => 'g',
    ]);

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

it('allows forced unchanged values without requiring write access', function (bool $readOnly, bool $locked) {
    $projectConfig = getFakeProjectConfig(['test' => 'value', 'dateModified' => 1609452000]);
    $projectConfig->readOnly = $readOnly;
    $lock = Cache::lock(ProjectConfig::MUTEX_NAME, 30);

    if ($locked) {
        expect($lock->get())->toBeTrue();
    }

    try {
        expect($projectConfig->set('test', 'value', force: true))->toBeTrue();
        expect($projectConfig->get('test'))->toBe('value');
        expect($projectConfig->get('dateModified'))->toBe(1609452000);
        expect($projectConfig->getAppliedChanges())->toBe([]);
    } finally {
        $lock->release();
        $projectConfig->reset();
    }
})->with([false, true])->with([false, true]);

it('finds child config arrays without returning the config root', function () {
    $config = ['first' => ['nested' => ['value' => 1]], 'second' => [], 'scalar' => true];
    $projectConfig = getFakeProjectConfig($config);

    expect($projectConfig->find(fn (array $item): bool => true))->toBe([
        'first' => $config['first'],
        'second' => [],
    ]);
});

it('loads descendant values over stale scalar ancestor rows', function (string $ancestorValue) {
    $projectConfig = app(ProjectConfig::class);
    DB::table(Table::PROJECTCONFIG)->insert([
        ['path' => 'legacyOverlap', 'value' => $ancestorValue],
        ['path' => 'legacyOverlap.deep.value', 'value' => '1'],
    ]);
    Cache::forget(ProjectConfig::STORED_CACHE_KEY);
    $projectConfig->reset();

    expect($projectConfig->get('legacyOverlap'))->toBe(['deep' => ['value' => 1]]);
})->with(['string' => '"obsolete"', 'integer' => '1', 'boolean' => 'false']);

it('replays forced external changes against the original config', function (bool $changed) {
    $original = ['value' => 'old'];
    $incoming = ['value' => $changed ? 'new' : 'old'];
    $projectConfig = getFakeProjectConfig(['plugin' => $original], ['plugin' => $incoming]);
    $handled = [];
    $projectConfig->onUpdate('plugin', function (ConfigEvent $event) use (&$handled) {
        $handled[] = [$event->oldValue, $event->newValue];
    });

    $projectConfig->processConfigChanges('plugin', true);
    $projectConfig->processConfigChanges('plugin', true);

    expect($handled)->toBe($changed ? [[$original, $incoming], [$original, $incoming]] : []);
})->with([true, false]);

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

    $history = $pc->getAppliedChanges();
    $delta = file_get_contents(Path::configDelta(ProjectConfig::CONFIG_DELTA_FILENAME));
    DB::enableQueryLog();
    $pc->flush();
    expect(DB::getQueryLog())->toBe([])
        ->and($pc->getAppliedChanges())->toBe($history)
        ->and(file_get_contents(Path::configDelta(ProjectConfig::CONFIG_DELTA_FILENAME)))->toBe($delta);
    DB::disableQueryLog();

    $pc->set('some.path', 'updated');
    $pc->remove('some.path');
    $pc->saveModifiedConfigData();

    expect(DB::table(Table::PROJECTCONFIG)->where('path', 'some.path')->exists())->toBeFalse()
        ->and($pc->getAppliedChanges())->toHaveCount(count($history) + 2)
        ->and(file_get_contents(Path::configDelta(ProjectConfig::CONFIG_DELTA_FILENAME)))->not->toContain('dateModified');

    Event::assertDispatched(ItemAdded::class);
    Event::assertDispatched(ItemUpdated::class);
    Event::assertDispatched(ItemRemoved::class);
});

it('persists changes again after an outer transaction rolls back', function () {
    $pc = getFakeProjectConfig();
    $pc->set('rollback-test', 'value');
    DB::beginTransaction();
    $pc->saveModifiedConfigData();
    DB::rollBack();

    expect(DB::table(Table::PROJECTCONFIG)->where('path', 'rollback-test')->exists())->toBeFalse();
    $pc->saveModifiedConfigData();
    expect(DB::table(Table::PROJECTCONFIG)->where('path', 'rollback-test')->value('value'))->toBe('"value"');
});

it('applies parent changes after processing descendants', function (array $incoming) {
    $projectConfig = getFakeProjectConfig([
        'test' => ['nested' => ['value' => 1, 'keep' => true], 'direct' => 1],
    ], $incoming);

    $projectConfig->applyConfigChanges($incoming);

    expect($projectConfig->get())->toBe($incoming);
})->with([
    'updates' => [['test' => ['nested' => ['value' => 2, 'keep' => true], 'direct' => 2]]],
    'removals' => [['test' => ['nested' => ['keep' => true]]]],
]);

it('ignores parent handlers for unrelated event types when applying sibling changes', function () {
    $incoming = ['test' => ['one' => ['value' => 2], 'two' => ['value' => 2]]];
    $projectConfig = getFakeProjectConfig([
        'test' => ['one' => ['value' => 1], 'two' => ['value' => 1]],
    ], $incoming);
    $handled = [];
    $projectConfig->onRemove('test', function () {
        throw new RuntimeException('Unexpected removal');
    });
    $projectConfig->onUpdate('test.{uid}', function (ConfigEvent $event) use (&$handled) {
        $handled[] = $event->path;
    });

    $projectConfig->applyConfigChanges($incoming);

    expect($handled)->toBe(['test.one', 'test.two']);
});

it('preserves list ordering after saving an indexed edit and reloading', function () {
    $projectConfig = app(ProjectConfig::class);
    $projectConfig->set('testList', range(0, 11));
    $projectConfig->saveModifiedConfigData();
    $projectConfig->set('testList.1', 100);
    $projectConfig->saveModifiedConfigData();
    $projectConfig->reset();

    $expected = [0, 100, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
    expect($projectConfig->get('testList'))->toBe($expected);
    expect($projectConfig->set('testList', $expected))->toBeFalse();
});

it('guards name mapping mutations against concurrent changes', function (bool $remove, bool $stale) {
    $uid = 'd62a289c-4a1a-4e4b-955e-3cf9973454e4';
    $projectConfig = getFakeProjectConfig(['meta' => ['__names__' => [$uid => 'Original']]]);
    $lock = Cache::lock(ProjectConfig::MUTEX_NAME, 30);

    if ($stale) {
        DB::table(Table::INFO)->update(['configVersion' => 'changed']);
    } else {
        expect($lock->get())->toBeTrue();
    }

    try {
        $change = $remove
            ? fn () => $projectConfig->removeNameMapping($uid)
            : fn () => $projectConfig->setNameMapping($uid, 'Updated');

        expect($change)->toThrow($stale ? StaleResourceException::class : BusyResourceException::class);
        expect($projectConfig->get("meta.__names__.$uid"))->toBe('Original');
    } finally {
        $lock->release();
        $projectConfig->reset();
    }
})->with([
    'set while locked' => [false, false],
    'remove while locked' => [true, false],
    'set with stale config' => [false, true],
    'remove with stale config' => [true, true],
]);

it('keeps name mapping changes out of timestamps and yaml exports', function (bool $remove) {
    $uid = 'd62a289c-4a1a-4e4b-955e-3cf9973454e4';
    $projectConfig = getFakeProjectConfig([
        'meta' => ['__names__' => [$uid => 'Original']],
        'dateModified' => 1609452000,
    ]);
    Event::fake([YamlFilesWritten::class]);

    if ($remove) {
        $projectConfig->removeNameMapping($uid);
    } else {
        $projectConfig->setNameMapping($uid, 'Updated');
    }

    $projectConfig->flush();

    expect($projectConfig->get("meta.__names__.$uid"))->toBe($remove ? null : 'Updated');
    expect($projectConfig->get('dateModified'))->toBe(1609452000);
    Event::assertNotDispatched(YamlFilesWritten::class);
})->with([false, true]);

it('applies user settings and group changes together', function (bool $existing) {
    $projectConfig = app(ProjectConfig::class);
    $uid = 'd62a289c-4a1a-4e4b-955e-3cf9973454e4';
    $projectConfig->set('users.allowPublicRegistration', false);

    if ($existing) {
        $projectConfig->set("users.groups.$uid", ['name' => 'Original group', 'handle' => 'configTestGroup']);
    }

    $incoming = $projectConfig->get();
    $incoming['users']['allowPublicRegistration'] = true;
    $incoming['users']['groups'][$uid] = ['name' => 'Updated group', 'handle' => 'configTestGroup'];

    $projectConfig->applyConfigChanges($incoming);
    $projectConfig->saveModifiedConfigData();

    expect($projectConfig->get('users.allowPublicRegistration'))->toBeTrue();
    expect(DB::table(Table::PROJECTCONFIG)->where('path', 'users.allowPublicRegistration')->value('value'))->toBe('true');
    expect(DB::table(Table::USERGROUPS)->where('uid', $uid)->value('name'))->toBe('Updated group');
})->with(['new group' => false, 'existing group' => true]);

it('starts each config application with fresh path claims', function (bool $fail) {
    $projectConfig = getFakeProjectConfig(['test' => true], ['test' => true]);
    $claims = [];
    Event::listen(ChangesApplied::class, function () use ($projectConfig, &$claims, $fail) {
        $claims[] = $projectConfig->claimPath(ProjectConfig::PATH_FIELDS);
        if ($fail && count($claims) === 1) {
            throw new RuntimeException('Application failed');
        }
    });

    if ($fail) {
        expect(fn () => $projectConfig->applyConfigChanges(['test' => true]))->toThrow(RuntimeException::class, 'Application failed');
    } else {
        $projectConfig->applyConfigChanges(['test' => true]);
    }
    $projectConfig->applyConfigChanges(['test' => true]);

    expect($claims)->toBe([true, true]);
    $projectConfig->reset();
    expect($projectConfig->claimPath(ProjectConfig::PATH_FIELDS, true))->toBeTrue();
    expect(new ProjectConfig(Cms::config())->claimPath(ProjectConfig::PATH_FIELDS, true))->toBeTrue();
})->with([false, true]);
