<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Exceptions\ImageException;
use CraftCms\Cms\Image\Images;
use CraftCms\Cms\Image\Raster;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->fixturesPath = dirname(__DIR__, 2).'/_data/assets/files';
    $this->sandboxPath = storage_path('framework/testing/raster');

    File::ensureDirectoryExists($this->sandboxPath);
    File::cleanDirectory($this->sandboxPath);

    foreach (['google.png', 'craft-logo.svg'] as $fixture) {
        copy($this->fixturesPath.'/'.$fixture, $this->sandboxPath.'/'.$fixture);
    }
});

it('loads raster images with dimensions and extension', function () {
    $path = $this->sandboxPath.'/google.png';
    $image = (new Raster)->loadImage($path);
    [$width, $height] = getimagesize($path);

    expect($image->getWidth())->toBe($width)
        ->and($image->getHeight())->toBe($height)
        ->and($image->getExtension())->toBe('png');
});

it('resizes and saves raster images', function () {
    $targetPath = $this->sandboxPath.'/resized.png';
    $image = (new Raster)
        ->loadImage($this->sandboxPath.'/google.png')
        ->resize(40, 30);

    expect($image->saveAs($targetPath))->toBeTrue()
        ->and(File::exists($targetPath))->toBeTrue();

    [$width, $height] = getimagesize($targetPath);

    expect($width)->toBe(40)
        ->and($height)->toBe(30);
});

it('crops raster images', function () {
    $image = (new Raster)
        ->loadImage($this->sandboxPath.'/google.png')
        ->crop(0, 25, 0, 10);

    expect($image->getWidth())->toBe(25)
        ->and($image->getHeight())->toBe(10);
});

it('loads raster images from svg when imagick is available', function () {
    if (! app(Images::class)->getIsImagick()) {
        $this->markTestSkipped('Raster SVG loading requires Imagick in this environment.');
    }

    $svg = file_get_contents($this->sandboxPath.'/craft-logo.svg');
    $targetPath = $this->sandboxPath.'/from-svg.png';
    $image = (new Raster)->loadFromSVG($svg);

    expect($image->getExtension())->toBe('png')
        ->and($image->saveAs($targetPath))->toBeTrue()
        ->and(File::exists($targetPath))->toBeTrue();
});

it('throws when loading a missing raster image', function () {
    expect(fn () => (new Raster)->loadImage($this->sandboxPath.'/missing.png'))
        ->toThrow(ImageException::class);
});
