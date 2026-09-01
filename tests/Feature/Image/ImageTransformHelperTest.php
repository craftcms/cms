<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Image\ImageTransformHelper;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

beforeEach(function () {
    Storage::extend('image-transform-helper-test-fake-remote', function ($app, array $config) {
        $adapter = new LocalFilesystemAdapter($config['root']);

        return new FilesystemAdapter(new Flysystem($adapter), $adapter, $config);
    });

    $this->remoteRoot = storage_path('framework/testing/image-transform-helper-test/remote-disk');

    config()->set('filesystems.disks.image-transform-helper-test-remote-disk', [
        'driver' => 'image-transform-helper-test-fake-remote',
        'root' => $this->remoteRoot,
    ]);

    $this->volume = Volume::factory()->create(['fs' => 'disk:image-transform-helper-test-remote-disk']);
    $this->folder = VolumeFolderModel::factory()->create(['volumeId' => $this->volume->id]);

    File::deleteDirectory(Path::assetSources());
});

test('re-downloads the cached source file when the remote object has changed since it was cached', function () {
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => $this->folder->id,
        'filename' => 'get-local-image-source-test.jpg',
        'kind' => 'image',
    ]);

    $disk = $asset->getVolume()->sourceDisk();
    $disk->put($asset->getPath(), 'original bytes');
    touch($this->remoteRoot.'/'.$asset->getPath(), time() - 100);

    $sourcePath = ImageTransformHelper::getLocalImageSource($asset);

    expect(file_get_contents($sourcePath))->toBe('original bytes');

    $disk->put($asset->getPath(), 'updated bytes');
    touch($this->remoteRoot.'/'.$asset->getPath(), time() + 100);

    $sourcePath = ImageTransformHelper::getLocalImageSource($asset);

    expect(file_get_contents($sourcePath))->toBe('updated bytes');
});

test('does not re-download the cached source file when the remote object is unchanged', function () {
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => $this->folder->id,
        'filename' => 'get-local-image-source-unchanged-test.jpg',
        'kind' => 'image',
    ]);

    $disk = $asset->getVolume()->sourceDisk();
    $disk->put($asset->getPath(), 'original bytes');
    touch($this->remoteRoot.'/'.$asset->getPath(), time() - 100);

    $firstSourcePath = ImageTransformHelper::getLocalImageSource($asset);

    $disk->put($asset->getPath(), 'this should not be downloaded');
    touch($this->remoteRoot.'/'.$asset->getPath(), time() - 50);

    $secondSourcePath = ImageTransformHelper::getLocalImageSource($asset);

    expect($secondSourcePath)->toBe($firstSourcePath)
        ->and(file_get_contents($secondSourcePath))->toBe('original bytes');
});
