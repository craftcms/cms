<?php

use CraftCms\Cms\Cms;
use CraftCms\Cms\ProjectConfig\Data\ReadOnlyProjectConfigData;
use CraftCms\Cms\ProjectConfig\Events\ItemAdded;
use CraftCms\Cms\ProjectConfig\Events\ItemRemoved;
use CraftCms\Cms\ProjectConfig\Events\ItemUpdated;
use CraftCms\Cms\ProjectConfig\Exceptions\ReadonlyException;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Cache::lock(ProjectConfig::MUTEX_NAME)->forceRelease();
});

afterEach(function () {
    Cache::lock(ProjectConfig::MUTEX_NAME)->forceRelease();
});

class FakeProjectConfig extends CraftCms\Cms\ProjectConfig\ProjectConfig
{
    public array $external = [
        'aa' => 'bb',
        'bb' => [
            'vc' => 'dd',
        ],
        'ee' => [11, 22, 33],
        'f' => 'g',
    ];

    public array $internal = [
        'a' => 'b',
        'b' => [
            'c' => 'd',
        ],
        'e' => [1, 2, 3],
        'f' => 'g',
        'randomString' => 'Entirely random',
        'dateModified' => 1609452000,
    ];

    #[\Override]
    public function getExternalConfig(): ReadOnlyProjectConfigData
    {
        return new ReadOnlyProjectConfigData($this->external, $this);
    }

    #[\Override]
    protected function getInternalConfig(): ReadOnlyProjectConfigData
    {
        return new ReadOnlyProjectConfigData($this->internal, $this);
    }

    #[\Override]
    protected function persistInternalConfigValues(array $values): void
    {
        // Do nothing
    }

    #[\Override]
    protected function removeInternalConfigValuesByPaths(array $paths): void
    {
        // Do nothing
    }

    #[\Override]
    public function writeYamlFiles(bool $force = false): void
    {
        // Do nothing
    }

    #[\Override]
    protected function updateConfigVersion(): void
    {
        // Do nothing
    }
}

function getFakeProjectConfig(?array $internal = null, ?array $external = null): FakeProjectConfig
{
    $projectConfig = new FakeProjectConfig(Cms::config());

    if (! is_null($internal)) {
        $projectConfig->internal = $internal;
    }

    if (! is_null($external)) {
        $projectConfig->external = $external;
    }

    return $projectConfig;
}

test('rebuild ignores readonly', function () {
    $projectConfig = app(ProjectConfig::class);
    $readOnly = $projectConfig->readOnly;
    $projectConfig->readOnly = true;

    // Must trigger exception
    $thrown = false;
    try {
        $projectConfig->set('oops', true);
    } catch (ReadonlyException $e) {
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
