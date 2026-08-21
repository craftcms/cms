<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\AssetDisallowedExtensionException;
use CraftCms\Cms\Asset\Exceptions\FileException;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Volumes;

beforeEach(function () {
    $this->import = app(Import::class);

    $root = storage_path('framework/testing/import-assets/'.bin2hex(random_bytes(4)));
    config()->set('filesystems.disks.import-test-disk', [
        'driver' => 'local',
        'root' => $root,
    ]);

    // Volume::factory() doesn't assign a fieldLayoutId by default, which leaves getFieldLayout()
    // returning an unsaved, id-less layout that ElementImporter::fieldLayout() can't resolve back
    // to the volume via its uid — so a real, persisted FieldLayout has to be attached explicitly.
    $this->volume = Volume::factory()->create([
        'name' => 'Imports',
        'handle' => 'imports',
        'fs' => 'disk:import-test-disk',
        'fieldLayoutId' => FieldLayout::factory()->create(['type' => Asset::class])->id,
    ]);

    $this->otherVolume = Volume::factory()->create([
        'name' => 'Other',
        'handle' => 'other',
        'fs' => 'disk:import-test-disk',
        'fieldLayoutId' => FieldLayout::factory()->create(['type' => Asset::class])->id,
    ]);

    $this->rootFolder = Folders::getRootFolderByVolumeId($this->volume->id);
    $this->otherRootFolder = Folders::getRootFolderByVolumeId($this->otherVolume->id);

    $volumeData = Volumes::getVolumeById($this->volume->id);

    $this->importer = ElementImporter::create()
        ->className(Asset::class)
        ->site(Sites::getPrimarySite()->handle)
        ->fieldLayout($volumeData->getFieldLayout())
        ->transformer(null);

    $this->makeTempFile = function (string $extension = 'txt', string $contents = 'hello world') {
        $path = AssetsHelper::tempFilePath($extension);
        file_put_contents($path, $contents);

        return $path;
    };
});

it('imports an asset from a local temp file, deducing the filename and defaulting the folder', function () {
    $tempFilePath = ($this->makeTempFile)('txt');
    $expectedFilename = pathinfo((string) $tempFilePath, PATHINFO_BASENAME);

    $this->import->importItem($this->importer, ['tempFilePath' => $tempFilePath]);

    $asset = Asset::find()->volumeId($this->volume->id)->one();

    expect($asset)->not->toBeNull()
        ->and($asset->filename)->toBe($expectedFilename)
        ->and($asset->folderId)->toBe($this->rootFolder->id);
});

it('uses an explicitly provided filename instead of deriving it from the temp file path', function () {
    $tempFilePath = ($this->makeTempFile)('txt');

    $this->import->importItem($this->importer, [
        'tempFilePath' => $tempFilePath,
        'filename' => 'custom-name.txt',
    ]);

    $asset = Asset::find()->volumeId($this->volume->id)->filename('custom-name.txt')->one();

    expect($asset)->not->toBeNull();
});

it('throws for a disallowed file extension', function () {
    $tempFilePath = ($this->makeTempFile)('exe');

    expect(fn () => $this->import->importItem($this->importer, ['tempFilePath' => $tempFilePath]))
        ->toThrow(AssetDisallowedExtensionException::class);
});

it('uses an explicitly provided valid folder ID', function () {
    $folder = VolumeFolder::factory()->create([
        'volumeId' => $this->volume->id,
        'parentId' => $this->rootFolder->id,
        'name' => 'Sub',
        'path' => 'sub/',
    ]);
    $tempFilePath = ($this->makeTempFile)('txt');

    $this->import->importItem($this->importer, [
        'tempFilePath' => $tempFilePath,
        'folderId' => $folder->id,
    ]);

    $asset = Asset::find()->volumeId($this->volume->id)->one();

    expect($asset->folderId)->toBe($folder->id);
});

it('resolves a folder provided by name', function () {
    $folder = VolumeFolder::factory()->create([
        'volumeId' => $this->volume->id,
        'parentId' => $this->rootFolder->id,
        'name' => 'ByName',
        'path' => 'by-name/',
    ]);
    $tempFilePath = ($this->makeTempFile)('txt');

    $this->import->importItem($this->importer, [
        'tempFilePath' => $tempFilePath,
        'folderId' => 'ByName',
    ]);

    $asset = Asset::find()->volumeId($this->volume->id)->one();

    expect($asset->folderId)->toBe($folder->id);
});

it('falls back to the target volume root folder when the given folder belongs to a different volume', function () {
    $foreignFolder = VolumeFolder::factory()->create([
        'volumeId' => $this->otherVolume->id,
        'parentId' => $this->otherRootFolder->id,
        'name' => 'Foreign',
        'path' => 'foreign/',
    ]);
    $tempFilePath = ($this->makeTempFile)('txt');

    $this->import->importItem($this->importer, [
        'tempFilePath' => $tempFilePath,
        'folderId' => $foreignFolder->id,
    ]);

    $asset = Asset::find()->volumeId($this->volume->id)->one();

    expect($asset->folderId)->toBe($this->rootFolder->id);
});

it('ignores an explicit volumeId in the incoming data in favor of the field layout provider volume', function () {
    $tempFilePath = ($this->makeTempFile)('txt');

    $this->import->importItem($this->importer, [
        'tempFilePath' => $tempFilePath,
        'volumeId' => $this->otherVolume->id,
    ]);

    $asset = Asset::find()->filename(pathinfo((string) $tempFilePath, PATHINFO_BASENAME))->one();

    expect($asset)->not->toBeNull()
        ->and($asset->volumeId)->toBe($this->volume->id);
});

it('throws for a local temp file path that resolves outside of all allowed roots', function () {
    // A real, existing file outside every allowed temp root — e.g. one under tests/, which
    // Path::system() excludes.
    $outsidePath = base_path('tests/fixtures-import-asset-'.bin2hex(random_bytes(4)).'.txt');
    file_put_contents($outsidePath, 'not allowed');

    try {
        $asset = new Asset;
        $asset->setVolumeId($this->volume->id);

        expect(fn () => $asset->setAttributesForImport(['tempFilePath' => $outsidePath]))
            ->toThrow(FileException::class);
    } finally {
        @unlink($outsidePath);
    }
});

it('throws for a local temp file path that does not exist on disk', function () {
    $asset = new Asset;
    $asset->setVolumeId($this->volume->id);

    expect(fn () => $asset->setAttributesForImport([
        'tempFilePath' => Path::temp('does-not-exist-'.bin2hex(random_bytes(4)).'.txt'),
    ]))->toThrow(FileException::class);
});
