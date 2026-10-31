<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Field\Events\AssetsUploadedFilesLocating;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

it('adds uploaded files to the assets already selected in the field', function () {
    actingAs(User::findOne());
    config()->set('filesystems.disks.temp-assets', ['driver' => 'local', 'root' => storage_path('framework/testing/temp-assets')]);
    config()->set('filesystems.disks.upload-destination', ['driver' => 'local', 'root' => storage_path('framework/testing/upload-destination')]);
    Storage::fake('temp-assets');
    Storage::fake('upload-destination');
    Cms::config()->tempAssetUploadDisk = 'temp-assets';

    $volume = Volume::factory()->create(['fs' => 'upload-destination']);
    $folder = VolumeFolder::factory()->create(['volumeId' => $volume->id, 'path' => '']);
    $existing = Asset::factory()->createElement(['volumeId' => $volume->id, 'folderId' => $folder->id]);
    $entry = Entry::factory()
        ->withField('images', Assets::class, settings: ['defaultUploadLocationSource' => "volume:$volume->uid"])
        ->createElementWithFields()->element;

    $path = tempnam(sys_get_temp_dir(), 'upload');
    file_put_contents($path, 'uploaded');
    Event::listen(AssetsUploadedFilesLocating::class, function (AssetsUploadedFilesLocating $event) use ($path) {
        $event->files[] = ['filename' => 'uploaded.txt', 'mimeType' => 'text/plain', 'path' => $path, 'type' => 'file'];
    });

    $entry->setFieldValue('images', [$existing->id]);
    expect(Elements::saveElement($entry))->toBeTrue();

    $related = $entry::find()->id($entry->id)->one()->getFieldValue('images')->all();
    expect(array_column($related, 'id'))->toHaveCount(2)->toContain($existing->id)
        ->and(array_column($related, 'filename'))->toContain('uploaded.txt');
});
