<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Http\Controllers\Assets\ImageEditorController;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransformHelper;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\Support\Facades\Assets;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Defer\DeferredCallbackCollection;
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

    $this->volume = Volume::factory()->create(['disk' => 'image-transform-helper-test-remote-disk']);
    $this->folder = VolumeFolderModel::factory()->create(['volumeId' => $this->volume->id]);

    File::deleteDirectory(Path::assetSources());
});

test('uses original remote sources for transforms and editor previews, then deletes them', function (bool $oldCachedSource) {
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => $this->folder->id,
        'filename' => 'original-source.png',
        'kind' => 'image',
        'width' => 1200,
        'height' => 700,
    ]);
    $image = imagecreatetruecolor(1200, 700);
    ob_start();
    imagepng($image);
    $original = ob_get_clean();
    $disk = $asset->getVolume()->sourceDisk();
    $disk->put($asset->getPath(), $original);
    touch($this->remoteRoot.'/'.$asset->getPath(), time() - 100);
    if ($oldCachedSource) {
        ob_start();
        imagepng(imagecreatetruecolor(600, 350));
        file_put_contents($asset->getImageTransformSourcePath(), ob_get_clean());
    }

    $transformPath = ImageTransformHelper::generateTransform($asset, new ImageTransform([
        'width' => 80,
        'height' => 35,
        'mode' => 'stretch',
    ]));
    $this->beforeApplicationDestroyed(fn () => File::delete($transformPath));
    $sourcePath = ImageTransformHelper::getLocalImageSource($asset);

    expect(array_slice(getimagesize($transformPath), 0, 2))->toBe([80, 35])
        ->and(file_get_contents($sourcePath))->toBe($original);

    $this->actingAs(User::findOne());
    Assets::partialMock()->shouldReceive('getImagePreviewUrl')->andThrow(new NotSupportedException);
    $response = app(ImageEditorController::class)->editImage(Request::create('/', 'GET', [
        'assetId' => $asset->id,
        'size' => 500,
    ]));

    expect(file_get_contents($response->getFile()->getPathname()))->toBe($original);

    app(DeferredCallbackCollection::class)->invoke();

    expect(is_file($sourcePath))->toBeFalse()
        ->and($disk->get($asset->getPath()))->toBe($original);
})->with(['downloaded' => false, 'ignoring a downscaled copy an earlier version cached' => true]);

test('downloads remote sources once per request, command, or queued job, into files of their own', function () {
    // Another request's download of a file with the same name
    $otherDownload = Path::temp('get-local-image-source-test.delimiter.other.jpg');
    file_put_contents($otherDownload, 'other download');
    $this->beforeApplicationDestroyed(fn () => File::delete($otherDownload));

    $asset = AssetModel::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => $this->folder->id,
        'filename' => 'get-local-image-source-test.jpg',
        'kind' => 'image',
    ]);
    $disk = $asset->getVolume()->sourceDisk();
    $disk->put($asset->getPath(), 'original bytes');

    $firstSourcePath = ImageTransformHelper::getLocalImageSource($asset);
    $disk->put($asset->getPath(), 'modified bytes');

    expect(ImageTransformHelper::getLocalImageSource($asset))->toBe($firstSourcePath)
        ->and(file_get_contents($firstSourcePath))->toBe('original bytes');

    app(DeferredCallbackCollection::class)->invoke();
    app()->forgetScopedInstances();

    $secondSourcePath = ImageTransformHelper::getLocalImageSource($asset);

    expect(is_file($firstSourcePath))->toBeFalse()
        ->and($secondSourcePath)->not->toBe($firstSourcePath)
        ->and(file_get_contents($secondSourcePath))->toBe('modified bytes')
        ->and(file_get_contents($otherDownload))->toBe('other download');
});

test('deletes its download when downloading the source fails', function (string $failure) {
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => $this->folder->id,
        'filename' => 'failed-source.jpg',
        'kind' => 'image',
    ]);
    $tempFilePath = Path::temp('failed-source.delimiter.owned.jpg');
    $paths = Mockery::mock(Path::getFacadeRoot())->makePartial();
    $paths->shouldReceive('temp')->with(Mockery::type('string'))->andReturn($tempFilePath);
    Path::swap($paths);
    $this->beforeApplicationDestroyed(fn () => File::delete($tempFilePath));
    $disk = $asset->getVolume()->sourceDisk();
    $disk->put($asset->getPath(), $failure === 'empty' ? '' : 'image bytes');

    $exception = new RuntimeException('Download failed');

    if ($failure === 'download') {
        $remoteDisk = Mockery::mock($disk)->makePartial();
        $remoteDisk->shouldReceive('readStream')->once()->andReturnUsing(function () use ($tempFilePath, $exception) {
            file_put_contents($tempFilePath, 'partial download');
            throw $exception;
        });
        Storage::set('image-transform-helper-test-remote-disk', $remoteDisk);
    }

    try {
        ImageTransformHelper::getLocalImageSource($asset);
        test()->fail('Expected the download to fail.');
    } catch (Throwable $caught) {
        if ($failure === 'download') {
            expect($caught)->toBe($exception, $caught->getMessage());
        } else {
            expect($caught)->toBeInstanceOf(FilesystemException::class);
        }
    }

    expect(is_file($tempFilePath))->toBeFalse();
})->with(['empty', 'download']);

test('interleaved downloads keep each others files', function () {
    $assets = collect(['first', 'second'])->map(fn (string $folder) => AssetModel::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => VolumeFolderModel::factory()->create(['volumeId' => $this->volume->id, 'path' => $folder.'/'])->id,
        'filename' => 'interleaved-source.jpg',
        'kind' => 'image',
    ]));
    $tempPaths = [];
    $paths = Path::getFacadeRoot();
    $mockPaths = Mockery::mock($paths)->makePartial();
    $mockPaths->shouldReceive('temp')->with(Mockery::type('string'))->andReturnUsing(function (string $filename) use ($paths, &$tempPaths) {
        return $tempPaths[] = $paths->temp($filename);
    });
    Path::swap($mockPaths);
    $this->beforeApplicationDestroyed(function () use (&$tempPaths) {
        File::delete($tempPaths);
    });

    $disk = $assets[0]->getVolume()->sourceDisk();
    $disk->put($assets[0]->getPath(), 'first image');
    $disk->put($assets[1]->getPath(), 'second image');
    $remoteDisk = Mockery::mock($disk)->makePartial();
    $remoteDisk->shouldReceive('readStream')->with($assets[0]->getPath())->once()->andReturnUsing(function () use ($assets, $disk, &$tempPaths) {
        file_put_contents($tempPaths[0], 'partial first image');
        $secondSource = ImageTransformHelper::getLocalImageSource($assets[1]);

        expect(file_get_contents($secondSource))->toBe('second image');
        expect(file_get_contents($tempPaths[0]))->toBe('partial first image');
        expect($secondSource)->toBe($tempPaths[1]);

        return $disk->readStream($assets[0]->getPath());
    });
    Storage::set('image-transform-helper-test-remote-disk', $remoteDisk);

    $firstSource = ImageTransformHelper::getLocalImageSource($assets[0]);

    expect($firstSource)->toBe($tempPaths[0])
        ->and(file_get_contents($firstSource))->toBe('first image')
        ->and(file_get_contents($tempPaths[1]))->toBe('second image')
        ->and($tempPaths)->toHaveCount(2);
});
