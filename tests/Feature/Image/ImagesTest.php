<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Image\Enums\ImageDriver;
use CraftCms\Cms\Image\Images;
use CraftCms\Cms\Image\Raster;
use CraftCms\Cms\Image\Svg;
use CraftCms\Cms\Support\Facades\Images as ImagesFacade;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->service = app(Images::class);
    $this->fixturesPath = dirname(__DIR__, 2).'/_data/assets/files';
    $this->sandboxPath = storage_path('framework/testing/images-service');

    File::ensureDirectoryExists($this->sandboxPath);
    File::cleanDirectory($this->sandboxPath);

    foreach (['dirty-svg.svg', 'image-rotated-180.jpg', 'craft-logo.svg', 'example-gif.gif', 'empty-file.text'] as $fixture) {
        copy($this->fixturesPath.'/'.$fixture, $this->sandboxPath.'/'.$fixture);
    }

    $this->service->setSupportedImageFormats(['jpg', 'jpeg', 'gif', 'png']);
});

afterEach(function () {
    $this->service->setSupportedImageFormats(['jpg', 'jpeg', 'gif', 'png']);
});

it('is a singleton', function () {
    expect(ImagesFacade::getFacadeRoot())->toBe(app(Images::class))
        ->and($this->service)->toBe(app(Images::class));
});

it('reports the active driver', function () {
    expect($this->service->getDriver())->toBeInstanceOf(ImageDriver::class)
        ->and($this->service->getIsGd())->toBe($this->service->getDriver() === ImageDriver::Gd)
        ->and($this->service->getIsImagick())->toBe($this->service->getDriver() === ImageDriver::Imagick)
        ->and($this->service->getIsVips())->toBe($this->service->getDriver() === ImageDriver::Vips);
});

describe('loadImage', function () {
    it('returns raster for non-svg images', function () {
        $image = $this->service->loadImage($this->fixturesPath.'/google.png');

        expect($image)->toBeInstanceOf(Raster::class);
    });

    it('returns svg for svg images', function () {
        $image = $this->service->loadImage($this->fixturesPath.'/craft-logo.svg');

        expect($image)->toBeInstanceOf(Svg::class);
    });

    it('rasterizes svg when requested', function () {
        if (! $this->service->getCanRasterizeSvg()) {
            $this->markTestSkipped('Rasterized SVG loading is not supported by the active image driver.');
        }

        $image = $this->service->loadImage($this->fixturesPath.'/craft-logo.svg', true, 500);

        expect($image)->toBeInstanceOf(Raster::class);
    });

    it('replaces percentage width and height attributes on SVG files', function () {
        $path = $this->fixturesPath.'/svg-pcts.svg';
        /** @var Svg $image */
        $image = $this->service->loadImage($path);
        $expectedContents = str_replace('width="100%" height="100%"', 'width="4167px" height="4167px"', file_get_contents($path));

        expect($image->getWidth())->toBe(4167)
            ->and($image->getHeight())->toBe(4167)
            ->and($image->getSvgString())->toBe($expectedContents);
    });
});

it('sanitizes dirty svgs', function () {
    $path = $this->sandboxPath.'/dirty-svg.svg';

    $this->service->cleanImage($path);

    $contents = file_get_contents($path);

    expect($contents)->not->toContain('<script>')
        ->and($contents)->not->toContain('<this>');
});

it('respects sanitizeSvgUploads config setting', function () {
    $path = $this->sandboxPath.'/dirty-svg.svg';
    $original = Cms::config()->sanitizeSvgUploads;

    Cms::config()->sanitizeSvgUploads = false;

    try {
        $this->service->cleanImage($path);

        $contents = file_get_contents($path);

        expect($contents)->toContain('<script>')
            ->and($contents)->toContain('<this>');
    } finally {
        Cms::config()->sanitizeSvgUploads = $original;
    }
});

it('includes baseline supported image formats', function () {
    $formats = $this->service->getSupportedImageFormats();

    expect($formats)->toContain('jpg', 'jpeg', 'gif', 'png');

    if ($this->service->getSupportsWebP()) {
        expect($formats)->toContain('webp');
    }

    if ($this->service->getSupportsAvif()) {
        expect($formats)->toContain('avif');
    }

    if ($this->service->getSupportsHeic()) {
        expect($formats)->toContain('heic');
    }
});

it('returns true for memory checks on svg and empty files', function () {
    expect($this->service->checkMemoryForImage($this->sandboxPath.'/craft-logo.svg'))->toBeTrue()
        ->and($this->service->checkMemoryForImage($this->sandboxPath.'/empty-file.text'))->toBeTrue();
});

it('returns null or false for exif methods on unsupported files', function () {
    $path = $this->sandboxPath.'/craft-logo.svg';

    expect($this->service->getExifData($path))->toBeNull()
        ->and($this->service->rotateImageByExifData($path))->toBeFalse()
        ->and($this->service->stripOrientationFromExifData($path))->toBeFalse();
});

it('respects transformGifs config setting', function () {
    if (! $this->service->getIsImagick()) {
        $this->markTestSkipped('Need Imagick to verify GIF transform behavior.');
    }

    $path = $this->sandboxPath.'/example-gif.gif';
    $original = Cms::config()->transformGifs;
    $oldContents = file_get_contents($path);

    try {
        Cms::config()->transformGifs = false;
        $this->service->cleanImage($path);

        expect(file_get_contents($path))->toBe($oldContents);

        Cms::config()->transformGifs = true;
        $this->service->cleanImage($path);

        expect(file_get_contents($path))->not->toBe($oldContents);
    } finally {
        Cms::config()->transformGifs = $original;
    }
});

it('returns expected exif data when imagick and exif are available', function () {
    if (! $this->service->getIsImagick()) {
        $this->markTestSkipped('Need Imagick to verify EXIF metadata.');
    }

    if (! extension_loaded('exif')) {
        $this->markTestSkipped('Need ext-exif to verify EXIF metadata.');
    }

    $exifData = $this->service->getExifData($this->sandboxPath.'/image-rotated-180.jpg') ?? [];

    expect($exifData)->toMatchArray([
        'ifd0.Orientation' => 4,
        'ifd0.XResolution' => '72/1',
        'ifd0.YResolution' => '72/1',
        'ifd0.ResolutionUnit' => 2,
        'ifd0.YCbCrPositioning' => 1,
    ]);
});

it('cleans orientation with imagick when available', function () {
    if (! $this->service->getIsImagick()) {
        $this->markTestSkipped('Need Imagick to verify orientation cleanup.');
    }

    $path = $this->sandboxPath.'/image-rotated-180.jpg';

    $this->service->cleanImage($path);

    $image = new Imagick($path);

    expect($image->getImageOrientation())->toBe(0);
});

it('removes orientation exif data when imagick and exif are available', function () {
    if (! $this->service->getIsImagick()) {
        $this->markTestSkipped('Need Imagick to verify EXIF cleanup.');
    }

    if (! extension_loaded('exif')) {
        $this->markTestSkipped('Need ext-exif to verify EXIF cleanup.');
    }

    $path = $this->sandboxPath.'/image-rotated-180.jpg';

    $this->service->cleanImage($path);

    $exifData = $this->service->getExifData($path) ?? [];

    expect($exifData)->not->toHaveKey('ifd0.Orientation');
});
