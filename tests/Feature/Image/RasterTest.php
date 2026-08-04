<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Exceptions\ImageException;
use CraftCms\Cms\Image\ImageHelper;
use CraftCms\Cms\Image\Images;
use CraftCms\Cms\Image\Raster;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->fixturesPath = dirname(__DIR__, 2).'/_data/assets/files';
    $this->sandboxPath = storage_path('framework/testing/raster');

    File::ensureDirectoryExists($this->sandboxPath);
    File::cleanDirectory($this->sandboxPath);

    foreach (['google.png', 'craft-logo.svg', 'example-gif.gif', 'example-webp-alpha.webp'] as $fixture) {
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
    if (! app(Images::class)->getCanRasterizeSvg()) {
        $this->markTestSkipped('Raster SVG loading is not supported by the active image driver.');
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

it('detects transparent and opaque images', function () {
    expect((new Raster)->loadImage($this->sandboxPath.'/example-webp-alpha.webp')->getIsTransparent())->toBeTrue()
        ->and((new Raster)->loadImage($this->sandboxPath.'/google.png')->getIsTransparent())->toBeFalse();
});

it('preserves PNG channel count', function () {
    $sourcePath = $this->sandboxPath.'/google.png';
    $targetPath = $this->sandboxPath.'/channels.png';

    (new Raster)->loadImage($sourcePath)->resize(40, 40)->saveAs($targetPath);

    expect(ImageHelper::pngImageInfo($targetPath)['channels'])
        ->toBe(ImageHelper::pngImageInfo($sourcePath)['channels']);
});

it('preserves animated GIF frames and timing', function () {
    if (! extension_loaded('imagick')) {
        $this->markTestSkipped('Imagick is required to inspect animation metadata.');
    }

    $sourcePath = $this->sandboxPath.'/example-gif.gif';
    $targetPath = $this->sandboxPath.'/animated.gif';

    (new Raster)->loadImage($sourcePath)->resize(100, 75)->saveAs($targetPath);

    $source = new Imagick($sourcePath);
    $target = new Imagick($targetPath);
    $sourceDelays = [];
    $targetDelays = [];

    foreach ($source as $frame) {
        $sourceDelays[] = $frame->getImageDelay();
    }
    foreach ($target as $frame) {
        $targetDelays[] = $frame->getImageDelay();
    }

    expect($target->getNumberImages())->toBe($source->getNumberImages())
        ->and($target->getImageIterations())->toBe($source->getImageIterations())
        ->and($targetDelays)->toBe($sourceDelays);
});

it('accounts for rotation in text bounds', function () {
    $image = (new Raster)->loadImage($this->sandboxPath.'/google.png');
    $image->setFontProperties(dirname(__DIR__, 3).'/packages/craftcms-legacy/cp/src/fonts/Craft.ttf', 20, '#000000');

    $unrotated = $image->getTextBox('A');
    $rotated = $image->getTextBox('A', 90);

    expect($rotated['width'])->toBe($unrotated['height'])
        ->and($rotated['height'])->toBe($unrotated['width']);
});

it('saves supported transform formats', function (string $format) {
    if (! app(Images::class)->supportsFormat($format)) {
        $this->markTestSkipped("The active image driver does not support $format.");
    }

    $targetPath = $this->sandboxPath.'/converted.'.$format;

    expect((new Raster)
        ->loadImage($this->sandboxPath.'/google.png')
        ->resize(40, 40)
        ->saveAs($targetPath))->toBeTrue()
        ->and(File::exists($targetPath))->toBeTrue()
        ->and(File::size($targetPath))->toBeGreaterThan(0);
})->with([
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
