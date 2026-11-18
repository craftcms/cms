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
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Str;
use Illuminate\Cache\Events\RetrievingKey;
use Illuminate\Cache\Events\RetrievingManyKeys;
use Illuminate\Cache\Events\WritingKey;
use Illuminate\Cache\Events\WritingManyKeys;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Queue\Events\Looping;
use Illuminate\Queue\WorkerOptions;
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

/**
 * Counts the cache reads or writes of the stored project config.
 */
function countStoredConfigCacheCalls(string ...$events): Closure
{
    $count = 0;
    Event::listen($events, function (RetrievingKey|RetrievingManyKeys|WritingKey|WritingManyKeys $event) use (&$count) {
        $keys = $event instanceof RetrievingKey || $event instanceof WritingKey ? [$event->key] : $event->keys;

        if (array_filter($keys, fn (string $key) => str_starts_with($key, ProjectConfig::STORED_CACHE_KEY)) !== []) {
            $count++;
        }
    });

    return function () use (&$count): int {
        return $count;
    };
}

it('reads the keys nearly every request uses with one cache read', function () {
    $projectConfig = app(ProjectConfig::class);
    $projectConfig->reset();
    $projectConfig->get('system.name');
    $projectConfig->reset();
    $reads = countStoredConfigCacheCalls(RetrievingKey::class, RetrievingManyKeys::class);

    expect($projectConfig->get('system.name'))->not->toBeNull()
        ->and($projectConfig->get(ProjectConfig::PATH_USERS))->not->toBeNull()
        ->and($reads())->toBe(1)
        ->and($projectConfig->get(ProjectConfig::PATH_SITES))->toBe($projectConfig->get()[ProjectConfig::PATH_SITES])
        ->and($reads())->toBe(3);
});

it('reloads a cached key after another process changes the config or the key is evicted', function (bool $evicted) {
    $projectConfig = app(ProjectConfig::class);
    DB::table(Table::PROJECTCONFIG)->insert(['path' => 'laterRoot.value', 'value' => '"Before"']);
    Cache::forget(ProjectConfig::STORED_CACHE_KEY);
    $projectConfig->reset();
    expect($projectConfig->get('laterRoot.value'))->toBe('Before');

    DB::table(Table::PROJECTCONFIG)->where('path', 'laterRoot.value')->update(['value' => '"After"']);

    if ($evicted) {
        Cache::forget(ProjectConfig::STORED_CACHE_KEY.':laterRoot');
    } else {
        Info::fetch()->update(['configVersion' => Str::random(12)]);
    }

    $projectConfig->reset();

    expect($projectConfig->get('laterRoot.value'))->toBe('After')
        ->and($projectConfig->get()['laterRoot']['value'])->toBe('After');
})->with(['config changed' => false, 'key evicted' => true]);

it('reads the database once per request when the cache keeps nothing', function () {
    config(['cache.stores.none' => ['driver' => 'null']]);
    Cache::setDefaultDriver('none');
    $projectConfig = app(ProjectConfig::class);
    $projectConfig->reset();
    expect($projectConfig->get('system.name'))->not->toBeNull();

    DB::table(Table::PROJECTCONFIG)->insert(['path' => 'laterRoot.value', 'value' => '"Later"']);

    expect($projectConfig->get('laterRoot.value'))->toBeNull()
        ->and($projectConfig->get())->not->toHaveKey('laterRoot');
});

it('leaves writing the cache to the request that holds the rebuild lock', function () {
    $projectConfig = app(ProjectConfig::class);
    DB::table(Table::PROJECTCONFIG)->insert(['path' => 'laterRoot.value', 'value' => '"Rebuilt"']);
    Info::fetch()->update(['configVersion' => Str::random(12)]);
    $projectConfig->reset();
    $lock = Cache::lock(ProjectConfig::STORED_CACHE_KEY.':rebuild', 10);
    $lock->get();

    try {
        expect($projectConfig->get('laterRoot.value'))->toBe('Rebuilt')
            ->and(Cache::get(ProjectConfig::STORED_CACHE_KEY)['version'] ?? null)->not->toBe(Info::fetch()->configVersion);
    } finally {
        $lock->release();
    }
});

it('reads the rebuilt config after another key is evicted', function () {
    $projectConfig = app(ProjectConfig::class);
    DB::table(Table::PROJECTCONFIG)->insert([
        ['path' => 'firstRoot.value', 'value' => '"Before"'],
        ['path' => 'secondRoot.value', 'value' => '"Second"'],
    ]);
    Cache::forget(ProjectConfig::STORED_CACHE_KEY);
    $projectConfig->reset();
    $projectConfig->get('firstRoot.value');
    $projectConfig->reset();
    expect($projectConfig->get('firstRoot.value'))->toBe('Before');

    DB::table(Table::PROJECTCONFIG)->where('path', 'firstRoot.value')->update(['value' => '"After"']);
    Cache::forget(ProjectConfig::STORED_CACHE_KEY.':secondRoot');

    expect($projectConfig->get('secondRoot.value'))->toBe('Second')
        ->and($projectConfig->get('firstRoot.value'))->toBe('After')
        ->and($projectConfig->get()['firstRoot']['value'])->toBe('After');
});

it('skips writing the cache when another request restored the evicted key', function () {
    $projectConfig = app(ProjectConfig::class);
    DB::table(Table::PROJECTCONFIG)->insert(['path' => 'laterRoot.value', 'value' => '"Later"']);
    Cache::forget(ProjectConfig::STORED_CACHE_KEY);
    $projectConfig->reset();
    $projectConfig->get('laterRoot.value');
    $projectConfig->reset();
    $key = ProjectConfig::STORED_CACHE_KEY.':laterRoot';
    $entry = Cache::get($key);
    Cache::forget($key);

    // Another request writes the entry back while this one reads the database
    DB::listen(function (QueryExecuted $query) use ($key, $entry) {
        if (str_contains($query->sql, Table::PROJECTCONFIG) && ! Cache::has($key)) {
            // Written to the store directly, so it isn't counted as this request's write
            Cache::getStore()->forever($key, $entry);
        }
    });
    $writes = countStoredConfigCacheCalls(WritingKey::class, WritingManyKeys::class);

    expect($projectConfig->get('laterRoot.value'))->toBe('Later')
        ->and($writes())->toBe(0);
});

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

it('fires parent handlers when a nested value creates the parent', function () {
    $projectConfig = getFakeProjectConfig();
    $handled = [];
    $projectConfig->onAdd('demo', function (ConfigEvent $event) use (&$handled) {
        $handled[] = [$event->oldValue, $event->newValue];
    });

    $projectConfig->set('demo.child.key', 1);

    expect($handled)->toBe([[null, ['child' => ['key' => 1]]]])
        ->and($projectConfig->get('demo'))->toBe(['child' => ['key' => 1]]);
});

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

it('refreshes the cached diff when only the YAML changes', function () {
    Cache::forget(ProjectConfig::DIFF_CACHE_KEY);
    $projectConfig = getFakeProjectConfig(['f' => 'stored'], ['f' => 'firstYaml']);

    expect(ProjectConfigHelper::diff())->toContain('firstYaml');

    new ReflectionClass($projectConfig)->getProperty('external')->setValue($projectConfig, ['f' => 'secondYaml']);

    expect(ProjectConfigHelper::diff())->toContain('secondYaml')->not->toContain('firstYaml');
});

it('writes the rebuilt cache in batches of at most 25 entries', function () {
    $rows = [];

    for ($i = 0; $i < 30; $i++) {
        $rows[] = ['path' => "batchRoot$i.value", 'value' => '"x"'];
    }

    DB::table(Table::PROJECTCONFIG)->insert($rows);
    Info::fetch()->update(['configVersion' => Str::random(12)]);
    $projectConfig = app(ProjectConfig::class);
    $projectConfig->cacheDuration = 3600;
    $projectConfig->reset();
    $batches = [];
    Event::listen(WritingManyKeys::class, function (WritingManyKeys $event) use (&$batches) {
        $batches[] = count($event->keys);
    });

    expect($projectConfig->get('batchRoot29.value'))->toBe('x')
        ->and(array_sum($batches))->toBeGreaterThan(30)
        ->and(max($batches))->toBeLessThanOrEqual(25);
});

it('loads changed project config before each queued job', function () {
    $projectConfig = app(ProjectConfig::class);
    DB::table(Table::PROJECTCONFIG)->upsert([['path' => 'system.name', 'value' => '"Before"']], ['path'], ['value']);
    Info::fetch()->update(['configVersion' => Str::random(12)]);
    $projectConfig->reset();
    expect($projectConfig->get('system.name'))->toBe('Before');

    // Another process changes the config while the worker waits
    DB::table(Table::PROJECTCONFIG)->where('path', 'system.name')->update(['value' => '"After"']);
    Info::fetch()->update(['configVersion' => Str::random(12)]);
    event(new Looping('database', 'default', new WorkerOptions));

    expect($projectConfig->get('system.name'))->toBe('After');
});

it('saves a queued job\'s project config changes before loading config for the next job', function () {
    $projectConfig = app(ProjectConfig::class);
    $projectConfig->reset();
    $projectConfig->set('queuedJobRoot.value', 'fromJob');

    event(new Looping('database', 'default', new WorkerOptions));

    expect(DB::table(Table::PROJECTCONFIG)->where('path', 'queuedJobRoot.value')->value('value'))->toBe('"fromJob"')
        ->and($projectConfig->get('queuedJobRoot.value'))->toBe('fromJob');
});
