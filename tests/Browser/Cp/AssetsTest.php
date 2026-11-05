<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::first());

    $this->root = storage_path('framework/testing/browser-assets');
    $this->uploads = storage_path('framework/testing/browser-uploads');
    File::deleteDirectory($this->root);
    File::ensureDirectoryExists($this->root);
    File::ensureDirectoryExists($this->uploads);
    config()->set('filesystems.disks.browser-assets', ['driver' => 'local', 'root' => $this->root]);

    $this->volume = Volume::factory()->create(['name' => 'Photos', 'handle' => 'photos', 'fs' => 'browser-assets']);
    $this->folder = VolumeFolder::factory()->create(['volumeId' => $this->volume->id, 'parentId' => null, 'name' => 'Photos', 'path' => '']);
    Volumes::reset();
});

afterEach(function () {
    File::deleteDirectory($this->root);
    File::deleteDirectory($this->uploads);
});

it('uploads a file', function () {
    File::put($file = "$this->uploads/field-notes.txt", 'Hello from the browser');

    visitCp('assets/photos')
        ->attach('input[name="assets-upload"]', $file)
        ->assertVisible('#main tbody >> role=link[name="Field notes"]');

    $asset = AssetElement::find()->volume('photos')->filename('field-notes.txt')->one();

    expect($asset)->not->toBeNull()
        ->and(file_get_contents("$this->root/field-notes.txt"))->toBe('Hello from the browser');
});

it('renames an asset', function () {
    File::put("$this->root/field-notes.txt", 'Hello');
    $asset = Asset::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => $this->folder->id,
        'filename' => 'field-notes.txt',
        'kind' => 'text',
    ]);
    $asset->title = 'Field notes';
    Elements::saveElement($asset);

    visitCp('assets/photos')
        ->click('#main tbody >> role=link[name="Field notes"]')
        ->assertValue('input[name="newFilename"]', 'field-notes.txt')
        ->type('input[name="newFilename"]', 'trip-notes.txt')
        ->click('#main >> role=button[name="Save"]')
        ->assertVisible('#main tbody >> role=link[name="Field notes"]')
        ->assertSeeIn('#main tbody', 'trip-notes.txt');

    expect(AssetElement::find()->id($asset->id)->one()->filename)->toBe('trip-notes.txt')
        ->and(File::exists("$this->root/trip-notes.txt"))->toBeTrue()
        ->and(File::exists("$this->root/field-notes.txt"))->toBeFalse();
});
