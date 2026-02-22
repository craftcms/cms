<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Data\Volume as VolumeData;
use CraftCms\Cms\Asset\Events\ApplyingVolumeDelete;
use CraftCms\Cms\Asset\Events\DeletingVolume;
use CraftCms\Cms\Asset\Events\SavingVolume;
use CraftCms\Cms\Asset\Events\VolumeDeleted;
use CraftCms\Cms\Asset\Events\VolumeSaved;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Volumes as VolumesFacade;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->volumes = app(Volumes::class);

    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/volumes-test/test-disk'),
    ]);
});

it('is a singleton', function () {
    expect(VolumesFacade::getFacadeRoot())->toBe(app(Volumes::class));
    expect($this->volumes)->toBe(app(Volumes::class));
});

it('can get all volumes', function () {
    expect($this->volumes->getAllVolumes())->toBeEmpty();
    expect($this->volumes->getAllVolumeIds())->toBeEmpty();
    expect($this->volumes->getTotalVolumes())->toBe(0);

    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    app()->forgetInstance(Volumes::class);
    $this->volumes = app(Volumes::class);

    expect($this->volumes->getAllVolumes()->pluck('id'))->toContain($volume->id);
    expect($this->volumes->getAllVolumeIds())->toContain($volume->id);
    expect($this->volumes->getTotalVolumes())->toBe(1);
});

it('can get viewable volumes in console', function () {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    app()->forgetInstance(Volumes::class);
    $this->volumes = app(Volumes::class);

    // In console, all volumes are viewable
    expect($this->volumes->getViewableVolumes()->pluck('id'))->toContain($volume->id);
    expect($this->volumes->getViewableVolumeIds())->toContain($volume->id);
    expect($this->volumes->getTotalViewableVolumes())->toBe(1);
});

it('can get a volume by id', function () {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    app()->forgetInstance(Volumes::class);
    $this->volumes = app(Volumes::class);

    expect($this->volumes->getVolumeById($volume->id))->toBeInstanceOf(VolumeData::class);
    expect($this->volumes->getVolumeById(999))->toBeNull();
});

it('can get a volume by uid', function () {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    app()->forgetInstance(Volumes::class);
    $this->volumes = app(Volumes::class);

    expect($this->volumes->getVolumeByUid($volume->uid))->toBeInstanceOf(VolumeData::class);
    expect($this->volumes->getVolumeByUid(Str::uuid()->toString()))->toBeNull();
});

it('can get a volume by handle', function () {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    app()->forgetInstance(Volumes::class);
    $this->volumes = app(Volumes::class);

    expect($this->volumes->getVolumeByHandle($volume->handle))->toBeInstanceOf(VolumeData::class);
    expect($this->volumes->getVolumeByHandle('some-other-handle'))->toBeNull();
});

it('can save a new volume', function () {
    Event::fake([
        SavingVolume::class,
        VolumeSaved::class,
    ]);

    Event::listen(SavingVolume::class, fn () => null);
    Event::listen(VolumeSaved::class, fn () => null);

    expect(Volume::count())->toBe(0);

    $this->volumes->saveVolume(new VolumeData([
        'name' => 'Test Volume',
        'handle' => 'testVolume',
        'fsHandle' => 'test-disk',
    ]));

    expect(Volume::count())->toBe(1);

    tap(Volume::firstOrFail(), function ($volume) {
        expect($volume->name)->toBe('Test Volume');
        expect($volume->handle)->toBe('testVolume');
    });

    Event::assertDispatchedOnce(SavingVolume::class);
    Event::assertDispatchedOnce(VolumeSaved::class);
});

it('can save an existing volume', function () {
    $this->volumes->saveVolume(new VolumeData([
        'name' => 'Original Name',
        'handle' => 'originalHandle',
        'fsHandle' => 'test-disk',
    ]));

    app()->forgetInstance(Volumes::class);
    $this->volumes = app(Volumes::class);

    $volume = $this->volumes->getVolumeByHandle('originalHandle');
    $volume->name = 'Updated Name';

    $this->volumes->saveVolume($volume);

    app()->forgetInstance(Volumes::class);
    $this->volumes = app(Volumes::class);

    expect($this->volumes->getVolumeByHandle('originalHandle')->name)->toBe('Updated Name');
});

it('returns false when validation fails on save', function () {
    $result = $this->volumes->saveVolume(new VolumeData([
        'name' => '',
        'handle' => '',
    ]));

    expect($result)->toBeFalse();
    expect(Volume::count())->toBe(0);
});

it('can delete a volume by id', function () {
    Event::fake([
        DeletingVolume::class,
        ApplyingVolumeDelete::class,
        VolumeDeleted::class,
    ]);

    Event::listen(DeletingVolume::class, fn () => null);
    Event::listen(ApplyingVolumeDelete::class, fn () => null);
    Event::listen(VolumeDeleted::class, fn () => null);

    $this->volumes->saveVolume(new VolumeData([
        'name' => 'Delete Me',
        'handle' => 'deleteMe',
        'fsHandle' => 'test-disk',
    ]));

    app()->forgetInstance(Volumes::class);
    $this->volumes = app(Volumes::class);

    expect(Volume::count())->toBe(1);

    $volume = $this->volumes->getVolumeByHandle('deleteMe');
    ProjectConfig::rebuild();

    expect($this->volumes->deleteVolumeById($volume->id))->toBeTrue();

    expect(Volume::count())->toBe(0);
    expect(Volume::withTrashed()->count())->toBe(1);

    Event::assertDispatchedOnce(DeletingVolume::class);
    Event::assertDispatchedOnce(ApplyingVolumeDelete::class);
    Event::assertDispatchedOnce(VolumeDeleted::class);
});

it('can delete a volume', function () {
    Event::fake([
        DeletingVolume::class,
        ApplyingVolumeDelete::class,
        VolumeDeleted::class,
    ]);

    Event::listen(DeletingVolume::class, fn () => null);
    Event::listen(ApplyingVolumeDelete::class, fn () => null);
    Event::listen(VolumeDeleted::class, fn () => null);

    $this->volumes->saveVolume(new VolumeData([
        'name' => 'Delete Me Too',
        'handle' => 'deleteMeToo',
        'fsHandle' => 'test-disk',
    ]));

    app()->forgetInstance(Volumes::class);
    $this->volumes = app(Volumes::class);

    expect(Volume::count())->toBe(1);

    $volume = $this->volumes->getVolumeByHandle('deleteMeToo');
    ProjectConfig::rebuild();

    expect($this->volumes->deleteVolume($volume))->toBeTrue();

    expect(Volume::count())->toBe(0);
    expect(Volume::withTrashed()->count())->toBe(1);

    Event::assertDispatchedOnce(DeletingVolume::class);
    Event::assertDispatchedOnce(ApplyingVolumeDelete::class);
    Event::assertDispatchedOnce(VolumeDeleted::class);
});

it('returns false when deleting a non-existent volume by id', function () {
    expect($this->volumes->deleteVolumeById(999))->toBeFalse();
});

it('can reorder volumes', function () {
    $this->volumes->saveVolume(new VolumeData([
        'name' => 'Volume A',
        'handle' => 'volumeA',
        'fsHandle' => 'test-disk',
        'subpath' => 'a',
    ]));

    $this->volumes->saveVolume(new VolumeData([
        'name' => 'Volume B',
        'handle' => 'volumeB',
        'fsHandle' => 'test-disk',
        'subpath' => 'b',
    ]));

    app()->forgetInstance(Volumes::class);
    $this->volumes = app(Volumes::class);

    $volumeA = $this->volumes->getVolumeByHandle('volumeA');
    $volumeB = $this->volumes->getVolumeByHandle('volumeB');

    expect($volumeA->sortOrder)->toBeLessThan($volumeB->sortOrder);

    ProjectConfig::rebuild();

    $this->volumes->reorderVolumes([$volumeB->id, $volumeA->id]);

    app()->forgetInstance(Volumes::class);
    $this->volumes = app(Volumes::class);

    expect($this->volumes->getVolumeByHandle('volumeB')->sortOrder)
        ->toBeLessThan($this->volumes->getVolumeByHandle('volumeA')->sortOrder);
});

it('returns the temporary volume', function () {
    $volume = $this->volumes->getTemporaryVolume();

    expect($volume)->toBeInstanceOf(VolumeData::class);
    expect($volume->name)->toBe('Temporary Uploads');
});

it('returns null for user photo volume when not configured', function () {
    expect($this->volumes->getUserPhotoVolume())->toBeNull();
});

it('stores volume in project config on save', function () {
    $this->volumes->saveVolume(new VolumeData([
        'name' => 'Config Volume',
        'handle' => 'configVolume',
        'fsHandle' => 'test-disk',
    ]));

    app()->forgetInstance(Volumes::class);
    $this->volumes = app(Volumes::class);

    $volume = $this->volumes->getVolumeByHandle('configVolume');
    $configPath = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_VOLUMES.'.'.$volume->uid;

    $projectConfigData = ProjectConfig::get($configPath);

    expect($projectConfigData)->not()->toBeNull();
    expect($projectConfigData['name'])->toBe('Config Volume');
    expect($projectConfigData['handle'])->toBe('configVolume');
});
