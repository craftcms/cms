<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Image\Enums\ImageDriver;
use CraftCms\Cms\Image\Images;
use CraftCms\Cms\Image\Raster;
use Illuminate\Support\Facades\File;

it('loads and transforms images with each driver', function (ImageDriver $driver) {
    $original = Cms::config()->imageDriver;
    Cms::config()->imageDriver = $driver->value;
    app()->forgetInstance(Images::class);

    try {
        $images = app(Images::class);
    } catch (Throwable $e) {
        Cms::config()->imageDriver = $original;
        app()->forgetInstance(Images::class);
        if ($driver === ImageDriver::Vips && getenv('CRAFT_REQUIRE_VIPS')) {
            throw $e;
        }
        $this->markTestSkipped($e->getMessage());
    }

    try {
        $sourcePath = dirname(__DIR__, 2).'/_data/assets/files/google.png';
        $transparentPath = dirname(__DIR__, 2).'/_data/assets/files/example-webp-alpha.webp';
        $targetPath = storage_path("framework/testing/resized-{$driver->value}.png");
        $compressedPath = storage_path("framework/testing/compressed-{$driver->value}.png");
        $uncompressedPath = storage_path("framework/testing/uncompressed-{$driver->value}.png");
        $orientationPath = storage_path("framework/testing/orientation-{$driver->value}.jpg");
        $autoQualityPath = storage_path("framework/testing/auto-quality-{$driver->value}.jpg");
        $image = $images->loadImage($sourcePath);
        $compressed = $images->loadImage($sourcePath);
        $uncompressed = $images->loadImage($sourcePath);

        $compressed->setQuality(1)->saveAs($compressedPath);
        $uncompressed->setQuality(100)->saveAs($uncompressedPath);
        copy(dirname(__DIR__, 2).'/_data/assets/files/image-rotated-180.jpg', $orientationPath);
        $images->cleanImage($orientationPath);
        $images->loadImage(dirname(__DIR__, 2).'/_data/assets/files/background.jpg')->saveAs($autoQualityPath, true);

        expect($images->getDriver())->toBe($driver)
            ->and($image)->toBeInstanceOf(Raster::class)
            ->and($image->resize(40, 30)->saveAs($targetPath))->toBeTrue()
            ->and(getimagesize($targetPath))->toMatchArray([40, 30])
            ->and($images->loadImage($transparentPath)->getIsTransparent())->toBeTrue()
            ->and(File::size($compressedPath))->toBeLessThan(File::size($uncompressedPath))
            ->and(File::size($autoQualityPath))->toBeGreaterThan(0)
            ->and($images->getExifData($orientationPath))->not->toHaveKey('ifd0.Orientation');
    } finally {
        Cms::config()->imageDriver = $original;
        app()->forgetInstance(Images::class);
    }
})->with(ImageDriver::cases());

it('encodes every format reported by each driver', function (ImageDriver $driver, string $format) {
    $original = Cms::config()->imageDriver;
    Cms::config()->imageDriver = $driver->value;
    app()->forgetInstance(Images::class);

    try {
        $images = app(Images::class);
    } catch (Throwable $e) {
        Cms::config()->imageDriver = $original;
        app()->forgetInstance(Images::class);
        if ($driver === ImageDriver::Vips && getenv('CRAFT_REQUIRE_VIPS')) {
            throw $e;
        }
        $this->markTestSkipped($e->getMessage());
    }

    if (! $images->supportsFormat($format)) {
        Cms::config()->imageDriver = $original;
        app()->forgetInstance(Images::class);
        $this->markTestSkipped("{$driver->value} does not support $format in this environment.");
    }

    try {
        $sourcePath = dirname(__DIR__, 2).'/_data/assets/files/google.png';
        $targetPath = storage_path("framework/testing/converted-{$driver->value}.$format");

        expect($images->loadImage($sourcePath)
            ->resize(40, 40)
            ->saveAs($targetPath))->toBeTrue()
            ->and(File::size($targetPath))->toBeGreaterThan(0);
    } finally {
        Cms::config()->imageDriver = $original;
        app()->forgetInstance(Images::class);
    }
})->with(ImageDriver::cases())->with([
    'jpeg' => ['jpg'],
    'gif' => ['gif'],
    'png' => ['png'],
    'webp' => ['webp'],
    'avif' => ['avif'],
    'bmp' => ['bmp'],
    'heic' => ['heic'],
    'ico' => ['ico'],
    'jpeg 2000' => ['jp2'],
    'jpeg xl' => ['jxl'],
    'tiff' => ['tiff'],
]);

it('converts CMYK without modifying the source', function (ImageDriver $driver) {
    $sourcePath = storage_path("framework/testing/cmyk-{$driver->value}.jpg");
    $targetPath = storage_path("framework/testing/cmyk-{$driver->value}.png");
    $fixture = new Imagick;
    $fixture->newImage(10, 10, 'red');
    $fixture->setImageFormat('jpeg');
    $fixture->transformImageColorspace(Imagick::COLORSPACE_CMYK);
    $fixture->writeImage($sourcePath);
    $fixture->clear();
    $sourceHash = hash_file('sha256', $sourcePath);

    $original = Cms::config()->imageDriver;
    Cms::config()->imageDriver = $driver->value;
    app()->forgetInstance(Images::class);

    try {
        $images = app(Images::class);
    } catch (Throwable $e) {
        Cms::config()->imageDriver = $original;
        app()->forgetInstance(Images::class);
        if ($driver === ImageDriver::Vips && getenv('CRAFT_REQUIRE_VIPS')) {
            throw $e;
        }
        $this->markTestSkipped($e->getMessage());
    }

    try {
        $images->loadImage($sourcePath)->saveAs($targetPath);
        $pixel = new Imagick($targetPath)->getImagePixelColor(0, 0)->getColor();

        expect(hash_file('sha256', $sourcePath))->toBe($sourceHash)
            ->and($pixel['r'])->toBeGreaterThan(200)
            ->and($pixel['g'])->toBeLessThan(80)
            ->and($pixel['b'])->toBeLessThan(80);
    } finally {
        Cms::config()->imageDriver = $original;
        app()->forgetInstance(Images::class);
    }
})->with(ImageDriver::cases());
