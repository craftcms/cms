<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetUploadHandler;
use CraftCms\Cms\Asset\Data\AssetIngest;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\User\Elements\User as UserElement;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    foreach (['preview-text-source', 'preview-text-volume'] as $disk) {
        config()->set("filesystems.disks.$disk", [
            'driver' => 'local',
            'root' => storage_path("framework/testing/$disk"),
        ]);
    }
    Storage::fake('preview-text-source');
    Storage::fake('preview-text-volume');

    $volume = Volume::factory()->create(['fs' => 'preview-text-volume']);
    $folderModel = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'path' => '',
    ]);
    $folder = app(Folders::class)->getFolderById($folderModel->id);

    $this->source = function (string $filename, string $contents): UploadedFile {
        Storage::disk('preview-text-source')->put($filename, $contents);

        return new UploadedFile(Storage::disk('preview-text-source'), $filename, $filename);
    };
    $this->upload = function (string $filename, string $contents) use ($folder): Asset {
        $result = app(AssetUploadHandler::class)->ingest(new AssetIngest(
            source: ($this->source)($filename, $contents),
            filename: $filename,
            mimeType: 'application/octet-stream',
            folder: $folder,
            sanitizeOnUpload: false,
        ));

        return Asset::findOne($result->asset->id);
    };
});

it('stores the start of an uploaded text file as its preview text', function () {
    $asset = ($this->upload)('notes.md', "# Notes\r\n\r\nSome text.");

    expect($asset->previewText)->toBe("# Notes\n\nSome text.");
});

it('doesn’t store preview text for other files', function () {
    expect(($this->upload)('archive.zip', 'zip bytes')->previewText)->toBeNull();
});

it('drops the preview text when a text file is replaced with another kind of file', function () {
    actingAs(UserElement::findOne());
    $asset = ($this->upload)('notes.txt', 'Some text.');

    app(AssetUploadHandler::class)->replace($asset->id, ($this->source)('archive.zip', 'zip bytes'));

    expect(Asset::findOne($asset->id)->previewText)->toBeNull();
});

it('shows a text file’s preview text in its edit page preview, but not its thumbnails', function () {
    actingAs(UserElement::findOne());
    $asset = ($this->upload)('notes.txt', 'Some text.');

    expect($asset->getPreviewHtml())->toContain('>Some text.</div>')
        ->and($asset->getThumbHtml(120))->not->toContain('Some text.');
});
