<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Image\Images;
use CraftCms\Cms\Image\ImageTransformHelper;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

use function Pest\Laravel\mock;

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
    $unrelatedPath = Path::temp('get-local-image-source-test.delimiter.unrelated.jpg');
    file_put_contents($unrelatedPath, 'unrelated download');
    $this->beforeApplicationDestroyed(fn () => File::delete($unrelatedPath));

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
    expect(file_get_contents($unrelatedPath))->toBe('unrelated download');
    expect(glob(Path::temp('get-local-image-source-test.delimiter.*.jpg')))->toBe([$unrelatedPath]);
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

test('cleans its staging file when downloading or storing the source fails', function (string $failure) {
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => $this->folder->id,
        'filename' => 'failed-source.jpg',
        'kind' => 'image',
    ]);
    $sourcePath = $asset->getImageTransformSourcePath();
    $tempFilePath = Path::temp('failed-source.delimiter.owned.jpg');
    $paths = Mockery::mock(Path::getFacadeRoot())->makePartial();
    $paths->shouldReceive('temp')->with(Mockery::type('string'))->andReturn($tempFilePath);
    Path::swap($paths);
    $this->beforeApplicationDestroyed(fn () => File::delete($tempFilePath));
    $disk = $asset->getVolume()->sourceDisk();
    $disk->put($asset->getPath(), $failure === 'empty' ? '' : 'image bytes');

    Cms::config()->maxCachedCloudImageSize = $failure === 'resize' ? 100 : 0;
    $exception = new RuntimeException('Download failed');

    if ($failure === 'download') {
        $remoteDisk = Mockery::mock($disk)->makePartial();
        $remoteDisk->shouldReceive('readStream')->once()->andReturnUsing(function () use ($tempFilePath, $exception) {
            file_put_contents($tempFilePath, 'partial download');
            throw $exception;
        });
        Storage::set('image-transform-helper-test-remote-disk', $remoteDisk);
    } elseif ($failure === 'copy') {
        File::makeDirectory($sourcePath);
    } elseif ($failure === 'resize') {
        $images = mock(Images::class);
        $images->shouldReceive('getSupportedImageFormats')->andReturn(['jpg']);
        $images->shouldReceive('loadImage')->once()->with($tempFilePath)->andThrow($exception);
    }

    try {
        ImageTransformHelper::getLocalImageSource($asset);
        test()->fail('Expected source staging to fail.');
    } catch (Throwable $caught) {
        if (in_array($failure, ['download', 'resize'], true)) {
            expect($caught)->toBe($exception, $caught->getMessage());
        } else {
            expect($caught)->toBeInstanceOf($failure === 'empty' ? FilesystemException::class : ErrorException::class);
        }
    }

    expect(is_file($tempFilePath))->toBeFalse();
})->with(['empty', 'download', 'copy', 'resize']);

test('interleaved downloads keep each others staging files', function () {
    Cms::config()->maxCachedCloudImageSize = 0;
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
        expect(is_file($tempPaths[1]))->toBeFalse();

        return $disk->readStream($assets[0]->getPath());
    });
    Storage::set('image-transform-helper-test-remote-disk', $remoteDisk);

    $firstSource = ImageTransformHelper::getLocalImageSource($assets[0]);

    expect(file_get_contents($firstSource))->toBe('first image');
    expect($tempPaths)->toHaveCount(2);
    expect($tempPaths[0])->not->toBe($tempPaths[1]);
    expect(is_file($tempPaths[0]))->toBeFalse();
});
