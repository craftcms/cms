<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\GarbageCollection\Actions\DeleteTrashedAssetFiles;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config()->set('filesystems.disks.gc-trashed-assets', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/gc-trashed-assets'),
    ]);
    $this->disk = Storage::fake('gc-trashed-assets');
});

afterEach(function () {
    Cms::config()->trashedAssetDisk(null);
});

it('deletes trashed asset files whose assets aren’t holding them anymore', function () {
    Cms::config()->trashedAssetDisk('gc-trashed-assets');
    $trashed = Asset::factory()->create(['trashedFileExists' => true]);
    $restored = Asset::factory()->create(['trashedFileExists' => false]);

    $this->disk->put("$trashed->id.jpg", 'kept');
    $this->disk->put("$restored->id.jpg", 'restored');
    $this->disk->put('999999.png', 'hard-deleted');
    $this->disk->put('notes.txt', 'not an asset file');

    app(DeleteTrashedAssetFiles::class)();

    expect($this->disk->allFiles())->toEqualCanonicalizing(["$trashed->id.jpg", 'notes.txt']);
});

it('does nothing without a trashed asset disk', function () {
    $this->disk->put('999999.png', 'hard-deleted');

    app(DeleteTrashedAssetFiles::class)();

    expect($this->disk->allFiles())->toBe(['999999.png']);
});
