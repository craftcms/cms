<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetUploadHandler;
use CraftCms\Cms\Asset\Data\AssetIngest;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\AssetIngestStatus;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config()->set('filesystems.disks.asset-ingest-source', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/asset-ingest-source'),
    ]);
    config()->set('filesystems.disks.asset-ingest-destination', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/asset-ingest-destination'),
    ]);
    Storage::fake('asset-ingest-source');
    Storage::fake('asset-ingest-destination');

    $this->volume = Volume::factory()->create(['fs' => 'asset-ingest-destination']);
    $folderModel = VolumeFolderModel::factory()->create([
        'volumeId' => $this->volume->id,
        'path' => '',
    ]);
    $this->folder = app(Folders::class)->getFolderById($folderModel->id);
    $this->source = function (string $path, string $contents): UploadedFile {
        Storage::disk('asset-ingest-source')->put($path, $contents);

        return new UploadedFile(Storage::disk('asset-ingest-source'), $path, basename($path));
    };
});

it('ingests a filesystem-backed file without an HTTP request', function () {
    $uploader = User::firstOrFail();

    $result = app(AssetUploadHandler::class)->ingest(new AssetIngest(
        source: ($this->source)('decoded/source.bin', 'asset contents'),
        filename: 'Proof Of Address.txt',
        mimeType: 'text/plain',
        folder: $this->folder,
        sanitizeOnUpload: false,
        uploaderId: $uploader->id,
    ));

    $asset = Asset::findOne($result->asset->id);

    expect($result->status)->toBe(AssetIngestStatus::Saved)
        ->and($asset)->not->toBeNull()
        ->and($asset->getFilename())->toBe('Proof-Of-Address.txt')
        ->and($asset->getMimeType())->toBe('text/plain')
        ->and($asset->uploaderId)->toBe($uploader->id)
        ->and(Storage::disk('asset-ingest-destination')->get($asset->getPath()))->toBe('asset contents');
});

it('rejects an ingest target without a persisted location under the create scenario', function () {
    $folder = new VolumeFolder;
    $folder->volumeId = $this->volume->id;
    $folder->path = '';

    $result = app(AssetUploadHandler::class)->ingest(new AssetIngest(
        source: ($this->source)('decoded/source.txt', 'asset contents'),
        filename: 'example.txt',
        mimeType: 'text/plain',
        folder: $folder,
        sanitizeOnUpload: false,
    ));

    expect($result->status)->toBe(AssetIngestStatus::Invalid)
        ->and($result->asset->errors()->has('newLocation'))->toBeTrue()
        ->and(Asset::find()->count())->toBe(0);
    Storage::disk('asset-ingest-destination')->assertDirectoryEmpty('/');
});

it('sanitizes images while ingesting them under the create scenario', function () {
    $unsafeSvg = <<<'SVG'
        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10">
            <script>alert('unsafe')</script>
            <rect width="10" height="10" fill="red" />
        </svg>
        SVG;

    $result = app(AssetUploadHandler::class)->ingest(new AssetIngest(
        source: ($this->source)('decoded/unsafe.svg', $unsafeSvg),
        filename: 'unsafe.svg',
        mimeType: 'image/svg+xml',
        folder: $this->folder,
        sanitizeOnUpload: true,
    ));

    expect($result->status)->toBe(AssetIngestStatus::Saved)
        ->and(Storage::disk('asset-ingest-destination')->get($result->asset->getPath()))->not->toContain('<script');
});

it('returns the existing asset when ingest resolves a filename conflict', function () {
    $uploads = app(AssetUploadHandler::class);
    $first = $uploads->ingest(new AssetIngest(
        source: ($this->source)('decoded/first.txt', 'first'),
        filename: 'example.txt',
        mimeType: 'text/plain',
        folder: $this->folder,
        sanitizeOnUpload: false,
    ));
    $second = $uploads->ingest(new AssetIngest(
        source: ($this->source)('decoded/second.txt', 'second'),
        filename: 'example.txt',
        mimeType: 'text/plain',
        folder: $this->folder,
        sanitizeOnUpload: false,
    ));

    expect($second->status)->toBe(AssetIngestStatus::Saved)
        ->and($second->asset->conflictingFilename)->toBe('example.txt')
        ->and($second->asset->getFilename())->not->toBe('example.txt')
        ->and($second->conflictingAsset?->id)->toBe($first->asset->id)
        ->and(Asset::find()->folderId($this->folder->id)->count())->toBe(2);
});
