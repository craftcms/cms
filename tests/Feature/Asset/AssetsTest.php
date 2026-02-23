<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Assets;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Events\BeforeReplaceAsset;
use CraftCms\Cms\Asset\Events\DefineThumbUrl;
use CraftCms\Cms\Asset\Events\RegisterPreviewHandler;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Support\Facades\Assets as AssetsFacade;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->assets = app(Assets::class);

    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/assets-test/test-disk'),
    ]);
});

it('is a singleton', function () {
    expect(AssetsFacade::getFacadeRoot())->toBe(app(Assets::class));
    expect($this->assets)->toBe(app(Assets::class));
});

it('can get an asset by id', function () {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    $assetModel = AssetModel::factory()->create([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
    ]);

    $asset = $this->assets->getAssetById($assetModel->id);

    expect($asset)->toBeInstanceOf(Asset::class);
});

it('returns null for non-existent asset id', function () {
    expect($this->assets->getAssetById(999))->toBeNull();
});

it('can get total assets', function () {
    expect($this->assets->getTotalAssets())->toBe(0);

    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    AssetModel::factory()->count(3)->create([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
    ]);

    expect($this->assets->getTotalAssets())->toBe(3);
});

it('dispatches DefineThumbUrl event in getThumbUrl', function () {
    Event::fake([DefineThumbUrl::class]);

    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => 'test.txt',
        'kind' => 'text',
    ]);

    $this->assets->getThumbUrl($asset, 100);

    Event::assertDispatched(fn (\CraftCms\Cms\Asset\Events\DefineThumbUrl $event) => $event->asset->id === $asset->id
        && $event->width === 100
        && $event->height === 100);
});

it('uses DefineThumbUrl event url when set', function () {
    Event::listen(DefineThumbUrl::class, function (DefineThumbUrl $event) {
        $event->url = 'https://example.com/custom-thumb.jpg';
    });

    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => 'test.jpg',
        'kind' => 'image',
    ]);

    $url = $this->assets->getThumbUrl($asset, 100);

    expect($url)->toBe('https://example.com/custom-thumb.jpg');
});

it('dispatches RegisterPreviewHandler event', function () {
    Event::fake([RegisterPreviewHandler::class]);

    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => 'test.txt',
        'kind' => 'text',
    ]);

    $this->assets->getAssetPreviewHandler($asset);

    Event::assertDispatched(RegisterPreviewHandler::class);
});

it('returns default preview handler for known asset kinds', function () {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);

    $textAsset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => 'test.txt',
        'kind' => Asset::KIND_TEXT,
    ]);

    $handler = $this->assets->getAssetPreviewHandler($textAsset);

    expect($handler)->toBeInstanceOf(\craft\assetpreviews\Text::class);
});

it('returns null preview handler for unknown asset kinds', function () {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);

    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => 'test.xyz',
        'kind' => 'unknown',
    ]);

    $handler = $this->assets->getAssetPreviewHandler($asset);

    expect($handler)->toBeNull();
});

it('can get temp asset upload filesystem', function () {
    $fs = $this->assets->getTempAssetUploadFs();

    expect($fs)->toBeInstanceOf(FsInterface::class);
});

it('can create a temp asset query', function () {
    $query = $this->assets->createTempAssetQuery();

    expect($query)->toBeInstanceOf(\craft\elements\db\AssetQuery::class);
});

it('can get name replacement in folder when no conflict', function () {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);

    app()->forgetInstance(Folders::class);

    $rootFolder = app(Folders::class)->getRootFolderByVolumeId($volume->id);

    $result = $this->assets->getNameReplacementInFolder('unique-file.jpg', $rootFolder->id);

    expect($result)->toBe('unique-file.jpg');
});

it('dispatches BeforeReplaceAsset event with filename', function () {
    Event::fake([BeforeReplaceAsset::class]);

    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
    ]);

    $tempFile = tempnam(sys_get_temp_dir(), 'craft_test_');
    file_put_contents($tempFile, 'test content');

    try {
        $this->assets->replaceAssetFile($asset, $tempFile, 'new-filename.jpg');
    } catch (Throwable) {
        // The save may fail due to missing filesystem setup, but the event should still fire
    }

    Event::assertDispatched(fn (\CraftCms\Cms\Asset\Events\BeforeReplaceAsset $event) => $event->asset->id === $asset->id
        && $event->filename === 'new-filename.jpg');

    @unlink($tempFile);
});

it('resets caches', function () {
    $this->assets->reset();

    // No error means the reset worked
    expect(true)->toBeTrue();
});
