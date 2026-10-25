<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetContents;
use CraftCms\Cms\Asset\AssetUploadHandler;
use CraftCms\Cms\Asset\Data\AssetIngest;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\User\Elements\User as UserElement;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    config()->set('filesystems.disks.asset-contents-source', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/asset-contents-source'),
    ]);
    config()->set('filesystems.disks.asset-contents-destination', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/asset-contents-destination'),
    ]);
    Storage::fake('asset-contents-source');
    $this->disk = Storage::fake('asset-contents-destination');

    $volume = Volume::factory()->create(['fs' => 'asset-contents-destination']);
    $folderModel = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'path' => '',
    ]);
    $folder = app(Folders::class)->getFolderById($folderModel->id);

    $this->upload = function (string $filename, string $contents) use ($folder): Asset {
        Storage::disk('asset-contents-source')->put($filename, $contents);

        $result = app(AssetUploadHandler::class)->ingest(new AssetIngest(
            source: new UploadedFile(Storage::disk('asset-contents-source'), $filename, $filename),
            filename: $filename,
            mimeType: File::getMimeTypeByExtension($filename) ?? 'application/octet-stream',
            folder: $folder,
            sanitizeOnUpload: false,
        ));

        return Asset::findOne($result->asset->id);
    };
    $this->findTrashed = fn (int $id): Asset => Asset::find()->id($id)->trashed()->one();
});

it('stores an uploaded text file’s contents', function () {
    $asset = ($this->upload)('notes.md', "# Notes\n\nSome text.");

    expect($asset->hasStoredContent)->toBeTrue()
        ->and(app(AssetContents::class)->get($asset->id))->toBe("# Notes\n\nSome text.");
});

it('doesn’t store the contents of other uploaded files', function () {
    $asset = ($this->upload)('archive.zip', 'zip bytes');

    expect($asset->hasStoredContent)->toBeFalse()
        ->and(app(AssetContents::class)->get($asset->id))->toBeNull();
});

it('doesn’t store the contents of text files that are too large', function () {
    $asset = ($this->upload)('notes.txt', str_repeat('x', AssetContents::MAX_SIZE + 1));

    expect($asset->hasStoredContent)->toBeFalse();
});

it('replaces stored contents when a text file is replaced with another kind of file', function () {
    actingAs(UserElement::findOne());
    $asset = ($this->upload)('notes.txt', 'Some text.');
    Storage::disk('asset-contents-source')->put('archive.zip', 'zip bytes');

    app(AssetUploadHandler::class)->replace(
        $asset->id,
        new UploadedFile(Storage::disk('asset-contents-source'), 'archive.zip', 'archive.zip'),
    );

    expect(app(AssetContents::class)->get($asset->id))->toBeNull();
});

it('puts a soft-deleted file back from its stored contents when the asset is restored', function () {
    $contents = "%PDF-1.7\x00\xff\xfe binary";
    $asset = ($this->upload)('report.pdf', $contents);
    $path = $asset->getPath();

    Elements::deleteElement($asset);

    expect($this->disk->exists($path))->toBeFalse()
        ->and(app(AssetContents::class)->get($asset->id))->toBe($contents);

    expect(Elements::restoreElement(($this->findTrashed)($asset->id)))->toBeTrue()
        ->and($this->disk->get($path))->toBe($contents)
        ->and(app(AssetContents::class)->get($asset->id))->toBeNull();
});

it('returns a soft-deleted file’s contents from the database', function () {
    $asset = ($this->upload)('report.pdf', 'pdf bytes');

    Elements::deleteElement($asset);

    expect($this->disk->exists($asset->getPath()))->toBeFalse()
        ->and(($this->findTrashed)($asset->id)->getContents())->toBe('pdf bytes');
});

it('keeps a restored text file’s contents', function () {
    $asset = ($this->upload)('notes.txt', 'Some text.');
    $path = $asset->getPath();

    Elements::deleteElement($asset);
    Elements::restoreElement(($this->findTrashed)($asset->id));

    expect($this->disk->get($path))->toBe('Some text.')
        ->and(app(AssetContents::class)->get($asset->id))->toBe('Some text.');
});

it('drops an asset’s stored contents when the asset is hard-deleted', function () {
    $asset = ($this->upload)('notes.txt', 'Some text.');

    Elements::deleteElement($asset, hardDelete: true);

    expect(app(AssetContents::class)->has($asset->id))->toBeFalse();
});

it('doesn’t hold onto files that are too large when their assets are soft-deleted', function () {
    $asset = ($this->upload)('video.mp4', str_repeat('x', AssetContents::MAX_SIZE + 1));

    Elements::deleteElement($asset);

    expect(app(AssetContents::class)->has($asset->id))->toBeFalse()
        ->and(Elements::restoreElement(($this->findTrashed)($asset->id)))->toBeFalse();
});

it('can’t restore an asset whose file is gone and wasn’t stored', function () {
    $asset = ($this->upload)('report.pdf', 'pdf bytes');

    Elements::deleteElement($asset);
    app(AssetContents::class)->set($asset->id, null);

    expect(Elements::restoreElement(($this->findTrashed)($asset->id)))->toBeFalse();
});
