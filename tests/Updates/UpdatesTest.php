<?php

declare(strict_types=1);

use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Updates\Data\Update;
use CraftCms\Cms\Updates\Data\UpdateRelease;
use CraftCms\Cms\Updates\Data\Updates as UpdatesData;
use CraftCms\Cms\Updates\Updates;
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
