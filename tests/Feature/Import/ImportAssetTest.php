<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\AssetDisallowedExtensionException;
use CraftCms\Cms\Asset\Exceptions\AssetException;
use CraftCms\Cms\Asset\Exceptions\FileException;
use CraftCms\Cms\Asset\Import\AssetImporter;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Cms;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\UrlValidator\UrlValidator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->import = app(Import::class);

    $this->diskRoot = storage_path('framework/testing/import-assets/'.bin2hex(random_bytes(4)));
    config()->set('filesystems.disks.import-test-disk', [
        'driver' => 'local',
        'root' => $this->diskRoot,
    ]);

    // Volume::factory() has no persisted field layout, which ElementImporter can't resolve.
    $this->volume = Volume::factory()->create([
        'name' => 'Imports',
        'handle' => 'imports',
        'fs' => 'import-test-disk',
        'fieldLayoutId' => FieldLayout::factory()->create(['type' => Asset::class])->id,
    ]);

    $this->otherVolume = Volume::factory()->create([
        'name' => 'Other',
        'handle' => 'other',
        'fs' => 'import-test-disk',
        'fieldLayoutId' => FieldLayout::factory()->create(['type' => Asset::class])->id,
    ]);

    $this->rootFolder = Folders::getRootFolderByVolumeId($this->volume->id);
    $this->otherRootFolder = Folders::getRootFolderByVolumeId($this->otherVolume->id);

    $volumeData = Volumes::getVolumeById($this->volume->id);

    $this->importer = AssetImporter::create()
        ->site(Sites::getPrimarySite()->handle)
        ->fieldLayout($volumeData->getFieldLayout())
        ->transformer(null);

    $this->tempFilePaths = [];
    $this->makeTempFile = function (string $extension = 'txt', string $contents = 'hello world') {
        $path = AssetsHelper::tempFilePath($extension);
        file_put_contents($path, $contents);
        $this->tempFilePaths[] = $path;

        return $path;
    };
});

afterEach(function () {
    foreach ($this->tempFilePaths ?? [] as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    if (isset($this->diskRoot)) {
        File::deleteDirectory($this->diskRoot);
    }
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

it('uses a folder ID provided as a numeric string', function () {
    $folder = VolumeFolder::factory()->create([
        'volumeId' => $this->volume->id,
        'parentId' => $this->rootFolder->id,
        'name' => 'Sub',
        'path' => 'sub/',
    ]);
    $tempFilePath = ($this->makeTempFile)('txt');

    $this->import->importItem($this->importer, [
        'tempFilePath' => $tempFilePath,
        'folderId' => (string) $folder->id,
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
    // Path::system() excludes tests/, so this file is outside every temp root.
    $outsidePath = base_path('tests/fixtures-import-asset-'.bin2hex(random_bytes(4)).'.txt');
    file_put_contents($outsidePath, 'not allowed');

    try {
        $asset = new Asset;
        $asset->setVolumeId($this->volume->id);

        expect(fn () => $this->importer->setAttributesForImport($asset, ['tempFilePath' => $outsidePath], []))
            ->toThrow(FileException::class);
    } finally {
        @unlink($outsidePath);
    }
});

it('throws for a local temp file path that does not exist on disk', function () {
    $asset = new Asset;
    $asset->setVolumeId($this->volume->id);

    expect(fn () => $this->importer->setAttributesForImport($asset, [
        'tempFilePath' => Path::temp('does-not-exist-'.bin2hex(random_bytes(4)).'.txt'),
    ], []))->toThrow(FileException::class);
});

it('takes the field layout from the volume when the settings form refreshes', function () {
    $importer = AssetImporter::create();

    $importer->refreshSettingsUi(['volume' => $this->volume->uid]);

    expect($importer->volume)->toBe($this->volume->uid)
        ->and($importer->fieldLayout)->toBe(Volumes::getVolumeById($this->volume->id)->getFieldLayout()->uid);
});

it('rejects a file larger than the maximum upload size', function () {
    Cms::config()->maxUploadFileSize = 5;
    $tempFilePath = ($this->makeTempFile)('txt', 'more than five bytes');

    expect(fn () => $this->import->importItem($this->importer, ['tempFilePath' => $tempFilePath]))
        ->toThrow(AssetException::class);

    expect(Asset::find()->volumeId($this->volume->id)->exists())->toBeFalse();
});

it('names an asset imported from a URL after its decoded filename', function () {
    Http::fake(['example.com/*' => Http::response('remote contents', 200, ['Content-Type' => 'text/plain'])]);

    $importer = new class extends AssetImporter
    {
        public static function urlValidator(?callable $resolver = null): UrlValidator
        {
            return parent::urlValidator(fn () => ['93.184.216.34']);
        }
    };
    $importer->site(Sites::getPrimarySite()->handle)
        ->fieldLayout(Volumes::getVolumeById($this->volume->id)->getFieldLayout())
        ->transformer(null);

    $this->import->importItem($importer, ['tempFilePath' => 'https://example.com/files/my%20notes.txt?v=2']);

    $asset = Asset::find()->volumeId($this->volume->id)->one();

    expect($asset->getFilename())->toBe(AssetsHelper::prepareAssetName('my notes.txt'))
        ->and($asset->getContents())->toBe('remote contents');
});
