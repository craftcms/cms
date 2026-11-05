<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetUploadHandler;
use CraftCms\Cms\Asset\Data\AssetIngest;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\FileException;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Events\ElementLifecycleDeleted;
use CraftCms\Cms\Element\Events\ElementRestored;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Support\Facades\Elements;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    foreach (['asset-trash-source', 'asset-trash-volume', 'asset-trash-trash'] as $disk) {
        config()->set("filesystems.disks.$disk", [
            'driver' => 'local',
            'root' => storage_path("framework/testing/$disk"),
        ]);
    }
    Storage::fake('asset-trash-source');
    $this->volumeDisk = Storage::fake('asset-trash-volume');
    $this->trashDisk = Storage::fake('asset-trash-trash');
    Cms::config()->trashedAssetDisk('asset-trash-trash');

    $volume = Volume::factory()->create(['fs' => 'asset-trash-volume']);
    $folderModel = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'path' => '',
    ]);
    $folder = app(Folders::class)->getFolderById($folderModel->id);

    $this->upload = function (string $filename, string $contents) use ($folder): Asset {
        Storage::disk('asset-trash-source')->put($filename, $contents);

        $result = app(AssetUploadHandler::class)->ingest(new AssetIngest(
            source: new UploadedFile(Storage::disk('asset-trash-source'), $filename, $filename),
            filename: $filename,
            mimeType: 'application/octet-stream',
            folder: $folder,
            sanitizeOnUpload: false,
        ));

        return Asset::findOne($result->asset->id);
    };
    $this->findTrashed = fn (int $id): Asset => Asset::find()->id($id)->trashed()->one();
});

afterEach(function () {
    Cms::config()->trashedAssetDisk(null);
});

it('moves a soft-deleted asset’s file to the trashed asset disk', function () {
    $asset = ($this->upload)('report.pdf', 'pdf bytes');

    Elements::deleteElement($asset);

    expect($this->volumeDisk->exists('report.pdf'))->toBeFalse()
        ->and($this->trashDisk->get("$asset->id.pdf"))->toBe('pdf bytes')
        ->and(($this->findTrashed)($asset->id)->trashedFileExists)->toBeTrue();
});

it('names trashed files without an extension after just their asset’s ID', function () {
    $asset = new Asset(['id' => 5]);
    $asset->setFilename('LICENSE');

    expect($asset->getTrashedFilePath())->toBe('5');
});

it('moves a restored asset’s file back into its volume', function () {
    $asset = ($this->upload)('report.pdf', 'pdf bytes');

    Elements::deleteElement($asset);

    expect(Elements::restoreElement(($this->findTrashed)($asset->id)))->toBeTrue()
        ->and($this->volumeDisk->get('report.pdf'))->toBe('pdf bytes')
        ->and($this->trashDisk->exists("$asset->id.pdf"))->toBeFalse()
        ->and(Asset::findOne($asset->id)->trashedFileExists)->toBeFalse();
});

it('deletes a soft-deleted asset’s file when there’s no trashed asset disk, and can’t restore it', function () {
    Cms::config()->trashedAssetDisk(null);
    $asset = ($this->upload)('report.pdf', 'pdf bytes');

    Elements::deleteElement($asset);

    expect($this->volumeDisk->exists('report.pdf'))->toBeFalse()
        ->and($this->trashDisk->allFiles())->toBe([])
        ->and(Elements::restoreElement(($this->findTrashed)($asset->id)))->toBeFalse();
});

it('can’t restore an asset once the trashed asset disk is no longer set', function () {
    $asset = ($this->upload)('report.pdf', 'pdf bytes');

    Elements::deleteElement($asset);
    Cms::config()->trashedAssetDisk(null);

    expect(Elements::restoreElement(($this->findTrashed)($asset->id)))->toBeFalse();
});

it('won’t restore an asset over a file that has taken its place', function () {
    $asset = ($this->upload)('report.pdf', 'pdf bytes');

    Elements::deleteElement($asset);
    $this->volumeDisk->put('report.pdf', 'newer bytes');

    expect(fn () => Elements::restoreElement(($this->findTrashed)($asset->id)))->toThrow(FileException::class)
        ->and($this->volumeDisk->get('report.pdf'))->toBe('newer bytes')
        ->and($this->trashDisk->get("$asset->id.pdf"))->toBe('pdf bytes')
        ->and(($this->findTrashed)($asset->id))->not->toBeNull();
});

it('deletes a trashed file when its asset is hard-deleted', function () {
    $asset = ($this->upload)('report.pdf', 'pdf bytes');

    Elements::deleteElement($asset);
    Elements::deleteElement(($this->findTrashed)($asset->id), hardDelete: true);

    expect($this->trashDisk->exists("$asset->id.pdf"))->toBeFalse()
        ->and(AssetModel::find($asset->id))->toBeNull();
});

it('doesn’t move files to the trashed asset disk when an asset is hard-deleted', function () {
    $asset = ($this->upload)('report.pdf', 'pdf bytes');

    Elements::deleteElement($asset, hardDelete: true);

    expect($this->volumeDisk->exists('report.pdf'))->toBeFalse()
        ->and($this->trashDisk->allFiles())->toBe([]);
});

it('leaves the files of assets deleted with their volume in place, and restores them', function () {
    $asset = ($this->upload)('report.pdf', 'pdf bytes');
    $asset->deletedWithVolume = true;
    $asset->keepFileOnDelete = true;

    Elements::deleteElement($asset);

    expect($this->volumeDisk->get('report.pdf'))->toBe('pdf bytes')
        ->and($this->trashDisk->allFiles())->toBe([])
        ->and(Elements::restoreElement(($this->findTrashed)($asset->id)))->toBeTrue();
});

it('leaves a file in its volume if trashing its asset is rolled back', function () {
    $asset = ($this->upload)('report.pdf', 'pdf bytes');
    Event::listen(ElementLifecycleDeleted::class, fn () => throw new RuntimeException('Nope'));

    expect(fn () => Elements::deleteElement($asset))->toThrow(RuntimeException::class)
        ->and($this->volumeDisk->get('report.pdf'))->toBe('pdf bytes')
        ->and($this->trashDisk->allFiles())->toBe([])
        ->and(Asset::findOne($asset->id)->trashedFileExists)->toBeFalse();
});

it('leaves a file in the trash if restoring its asset is rolled back', function () {
    $asset = ($this->upload)('report.pdf', 'pdf bytes');
    Elements::deleteElement($asset);
    Event::listen(ElementRestored::class, fn () => throw new RuntimeException('Nope'));

    expect(fn () => Elements::restoreElement(($this->findTrashed)($asset->id)))->toThrow(RuntimeException::class)
        ->and($this->volumeDisk->exists('report.pdf'))->toBeFalse()
        ->and($this->trashDisk->get("$asset->id.pdf"))->toBe('pdf bytes')
        ->and(($this->findTrashed)($asset->id)->trashedFileExists)->toBeTrue();
});

it('keeps a trashed file if hard-deleting its asset is rolled back', function () {
    $asset = ($this->upload)('report.pdf', 'pdf bytes');
    Elements::deleteElement($asset);
    Event::listen(ElementLifecycleDeleted::class, fn () => throw new RuntimeException('Nope'));

    expect(fn () => Elements::deleteElement(($this->findTrashed)($asset->id), hardDelete: true))->toThrow(RuntimeException::class)
        ->and($this->trashDisk->get("$asset->id.pdf"))->toBe('pdf bytes');
});
