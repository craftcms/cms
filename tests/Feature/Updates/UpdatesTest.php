<?php

declare(strict_types=1);

use CraftCms\Cms\License\License;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\Composer;
use CraftCms\Cms\Update\Data\Update;
use CraftCms\Cms\Update\Data\UpdateRelease;
use CraftCms\Cms\Update\Data\Updates as UpdatesData;
use CraftCms\Cms\Update\Events\CriticalUpdateReleased;
use CraftCms\Cms\Update\Updates;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->updates = app(Updates::class);

    Http::fake([
        Api::craftApiEndpoint().'/updates' => [
            'cms' => [],
            'plugins' => [],
        ],
    ]);
});

it('is a singleton', function () {
    expect($this->updates)->toBe(app(Updates::class));
});

it('can check if update info is cached', function () {
    expect($this->updates->isUpdateInfoCached())->toBe(false);

    Cache::put(Updates::class, new UpdatesData);

    expect($this->updates->isUpdateInfoCached())->toBe(true);

    Cache::forget(Updates::class);

    expect($this->updates->isUpdateInfoCached())->toBe(false);

    $this->updates->cacheUpdates(new UpdatesData);

    expect($this->updates->isUpdateInfoCached())->toBe(true);
});

it('can determine the total amount of available updates', function () {
    expect($this->updates->totalAvailableUpdates())->toBe(0);

    $this->updates->cacheUpdates(new UpdatesData(
        cms: new Update(releases: [new UpdateRelease('1.0.0')]),
    ));

    expect($this->updates->totalAvailableUpdates())->toBe(1);
});

it('can determine if a critical update is available', function () {
    expect($this->updates->isCriticalUpdateAvailable())->toBe(false);

    $this->updates->cacheUpdates(new UpdatesData(
        cms: new Update(releases: [new UpdateRelease('1.0.0', critical: false)]),
    ));

    expect($this->updates->isCriticalUpdateAvailable())->toBe(false);

    $this->updates->cacheUpdates(new UpdatesData(
        cms: new Update(releases: [new UpdateRelease('1.0.0', critical: true)]),
    ));

    expect($this->updates->isCriticalUpdateAvailable())->toBe(true);
});

it('can get updates', function () {
    $this->updates->cacheUpdates($updatesData = new UpdatesData(
        cms: new Update(releases: [new UpdateRelease('1.0.0')]),
    ));

    expect($this->updates->getUpdates())->toBe($updatesData);
    expect($this->updates->totalAvailableUpdates())->toBe(1);

    expect($this->updates->getUpdates(true))->not()->toBe($updatesData);
    expect($this->updates->totalAvailableUpdates())->toBe(0);
});

it('can return if a craft update is pending', function () {
    expect($this->updates->isCraftUpdatePending())->toBe(false);

    Info::fetch()->update([
        'schemaVersion' => '0.0.0.0',
    ]);

    app()->forgetInstance(Updates::class);
    $this->updates = app(Updates::class);

    expect($this->updates->isCraftUpdatePending())->toBe(true);
    expect($this->updates->isUpdatePending())->toBe(true);
});

it('can return if migrations are pending', function () {
    expect($this->updates->areMigrationsPending())->toBe(false);

    /**
     * If a Craft update is pending, migrations are automatically pending
     */
    Info::fetch()->update([
        'schemaVersion' => '0.0.0.0',
    ]);

    app()->forgetInstance(Updates::class);
    $this->updates = app(Updates::class);

    expect($this->updates->areMigrationsPending())->toBe(true);

    $this->updates->updateCraftVersionInfo();

    expect($this->updates->areMigrationsPending())->toBe(false);
});

it('formats CLI updates with the original package and selected version', function (?string $pinned, bool $critical, bool $expired = false, bool $info = false) {
    $target = $pinned ?? '2.0.0';
    $this->mock(License::class)->shouldReceive('key')->andReturn('test-license');
    $this->mock(Api::class)->shouldReceive('getUpdates')->once()->andReturn([
        'cms' => ['status' => 'eligible'],
        'plugins' => ['example' => [
            'status' => $expired ? 'expired' : 'eligible', 'packageName' => 'new/package',
            'releases' => [['version' => '2.0.0', 'critical' => true]],
        ]],
    ]);
    $this->partialMock(Plugins::class)->shouldReceive('getPluginInfo')->with('example')->andReturn([
        'isInstalled' => true, 'version' => '1.0.0', 'packageName' => 'old/package',
    ]);
    $this->mock(Composer::class)->shouldReceive('install')->times($info ? 0 : 1)
        ->with(['new/package' => "^$target", 'old/package' => false], Mockery::type('callable'));
    $this->mock(Updates::class)->shouldReceive('getUpdates')->with(true)->andReturn(new UpdatesData);
    $seen = [];
    Event::listen(CriticalUpdateReleased::class, function ($event) use (&$seen, $critical) {
        $seen[] = $event->update->packageName;
        $event->isValid = $critical;
    });

    $this->withoutMockingConsoleOutput();
    expect(Artisan::call($info ? 'craft:update:info' : 'craft:update', $info ? [] : [
        'handle' => ['example'.($pinned ? ":$pinned" : '')], '--no-migrate' => true, '--with-expired' => $expired,
    ]))->toBe(0);
    $output = Artisan::output();
    expect($output)->toContain($target)
        ->and(str_contains($output, 'CRITICAL'))->toBe($critical);

    expect($seen)->toBe(['new/package']);
})->with([[null, true], ['1.5.0', true], [null, false], [null, true, true], [null, true, false, true]]);
