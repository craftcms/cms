<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Image\Data\ImageColors;
use CraftCms\Cms\Image\Enums\ImageDriver;
use CraftCms\Cms\Image\Images;
use CraftCms\Cms\Image\Raster;
use CraftCms\Cms\Image\Svg;
use CraftCms\Cms\Support\Facades\Images as ImagesFacade;
use Illuminate\Support\Facades\File;
use Intervention\Image\FileExtension;
use Intervention\Image\Interfaces\DriverInterface;

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

it('discovers additional image formats once and preserves configured formats and driver changes', function () {
    $driver = Mockery::mock(DriverInterface::class);
    $calls = [];
    $driver->shouldReceive('supports')->andReturnUsing(function (FileExtension $extension) use (&$calls): bool {
        $calls[] = $extension->value;

        return $extension === FileExtension::WEBP;
    });
    $this->service->getManager()->driver = $driver;

    expect($this->service->getSupportedImageFormats())->toBe(['jpg', 'jpeg', 'gif', 'png', 'webp']);
    $firstCalls = $calls;

    expect($this->service->getSupportedImageFormats())->toBe(['jpg', 'jpeg', 'gif', 'png', 'webp'])
        ->and($calls)->toBe($firstCalls);

    $this->service->setSupportedImageFormats(['custom']);

    expect($this->service->getSupportedImageFormats())->toBe(['custom', 'webp'])
        ->and($calls)->toBe($firstCalls);

    $replacement = Mockery::mock(DriverInterface::class);
    $replacement->shouldReceive('supports')->andReturnUsing(fn (FileExtension $extension): bool => $extension === FileExtension::AVIF);
    $this->service->getManager()->driver = $replacement;

    expect($this->service->getSupportedImageFormats())->toBe(['custom', 'avif']);
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

describe('colors', function () {
    /**
     * @param  array{int, int, int}  $background
     * @param  array{int, int, int, int, int, int, int}|null  $rectangle  x1, y1, x2, y2, r, g, b
     */
    function colorsFixture(string $path, array $background, ?array $rectangle = null, int $width = 200, int $height = 200): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocate($image, ...$background));

        if ($rectangle !== null) {
            [$x1, $y1, $x2, $y2, $r, $g, $b] = $rectangle;
            imagefilledrectangle($image, $x1, $y1, $x2, $y2, imagecolorallocate($image, $r, $g, $b));
        }

        imagepng($image, $path);

        return $path;
    }

    it('samples a single-color image', function () {
        $path = colorsFixture($this->sandboxPath.'/red.png', [200, 30, 40]);

        expect($this->service->colors($path)->toArray())->toBe([
            'dominant' => '#c81e28',
            'grid' => array_fill(0, 3, array_fill(0, 4, '#c81e28')),
        ]);
    });

    it('averages each region of the image into the grid', function () {
        $path = colorsFixture($this->sandboxPath.'/split.png', [200, 30, 40], [100, 0, 199, 149, 30, 80, 200], width: 200, height: 150);

        expect($this->service->colors($path)->grid)
            ->toBe(array_fill(0, 3, ['#c81e28', '#c81e28', '#1e50c8', '#1e50c8']));
    });

    it('averages each region in linear light', function () {
        $image = imagecreatetruecolor(8, 3);
        for ($x = 0; $x < 8; $x++) {
            imagefilledrectangle($image, $x, 0, $x, 2, $x % 2 ? imagecolorallocate($image, 255, 255, 255) : imagecolorallocate($image, 0, 0, 0));
        }
        imagepng($image, $path = $this->sandboxPath.'/stripes.png');

        expect($this->service->colors($path)->grid)->toBe(array_fill(0, 3, array_fill(0, 4, '#bcbcbc')));
    });

    it('keeps transparent regions transparent in the grid', function () {
        $image = imagecreatetruecolor(200, 150);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 30, 40));
        imagefilledrectangle($image, 100, 0, 199, 149, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagepng($image, $path = $this->sandboxPath.'/transparent.png');

        $alphas = array_map(
            fn (array $row): array => array_map(fn (string $color): string => substr($color, 7) ?: 'ff', $row),
            $this->service->colors($path)->grid,
        );

        expect($alphas)->toBe(array_fill(0, 3, ['ff', 'ff', '00', '00']));
    });

    it('turns the grid on its side for portrait images', function () {
        $path = colorsFixture($this->sandboxPath.'/portrait.png', [200, 30, 40], width: 150, height: 200);

        expect($this->service->colors($path)->grid)
            ->toBe(array_fill(0, 4, array_fill(0, 3, '#c81e28')));
    });

    it('passes over a white backdrop for the dominant color in front of it', function () {
        $path = colorsFixture($this->sandboxPath.'/white.png', [255, 255, 255], [60, 60, 120, 120, 30, 80, 200]);

        expect($this->service->colors($path)->dominant)->toBe('#1e50c8');
    });

    it('passes over a black backdrop for the dominant color in front of it', function () {
        $path = colorsFixture($this->sandboxPath.'/black.png', [0, 0, 0], [0, 0, 40, 40, 240, 140, 20]);

        expect($this->service->colors($path)->dominant)->toBe('#f08c14');
    });

    it('falls back to a white dominant color when there’s nothing else', function () {
        $path = colorsFixture($this->sandboxPath.'/all-white.png', [255, 255, 255]);

        expect($this->service->colors($path)->dominant)->toBe('#ffffff');
    });

    it('returns empty colors for files it can’t read as images', function () {
        expect($this->service->colors($this->sandboxPath.'/empty-file.text'))->toEqual(new ImageColors)
            ->and($this->service->colors($this->sandboxPath.'/missing.png'))->toEqual(new ImageColors);
    });
});
