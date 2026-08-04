<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Image\ImageHelper;
use CraftCms\Cms\Image\Images;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->service = app(Images::class);
    $this->fixturesPath = dirname(__DIR__, 2).'/_data/assets/files';
    $this->sandboxPath = storage_path('framework/testing/image-helper');

    File::ensureDirectoryExists($this->sandboxPath);
    File::cleanDirectory($this->sandboxPath);

    foreach ([
        'background.jpeg',
        'background.jpg',
        'broken-gif-signature.gif',
        'broken-jpg-structure.jpg',
        'broken-png-signature.png',
        'craft-logo.svg',
        'dirty-svg.svg',
        'empty-file.text',
        'example-avif.avif',
        'example-gif.gif',
        'example-heic.heic',
        'example-heif.heif',
        'example-webp-alpha.webp',
        'example-webp-lossless.webp',
        'example-webp.webp',
        'gng.svg',
        'google.png',
        'ign.jpg',
        'invalid-ihdr.png',
        'no-dimension-svg.svg',
        'no-ihdr.png',
        'random.tif',
        'random.tiff',
    ] as $fixture) {
        copy($this->fixturesPath.'/'.$fixture, $this->sandboxPath.'/'.$fixture);
    }

    $this->service->setSupportedImageFormats(['jpg', 'jpeg', 'gif', 'png']);
});

afterEach(function () {
    $this->service->setSupportedImageFormats(['jpg', 'jpeg', 'gif', 'png']);
});

it('calculates missing dimensions', function (array $expected, float|int|null $targetWidth, float|int|null $targetHeight, float|int $sourceWidth, float|int $sourceHeight) {
    expect(ImageHelper::calculateMissingDimension($targetWidth, $targetHeight, $sourceWidth, $sourceHeight))
        ->toBe($expected);
})->with([
    [[1, 1], 1, 1, 1, 1],
    [[10, 2], 10, 2, 4, 2],
    [[4, 2], 0, 2, 4, 2],
    [[2, 1], 2, 0, 4, 2],
    [[4, 2], 0, 0, 4.2891, 2.12321],
    [[28971, 14341], 28971.251, 0, 4.2891, 2.12321],
    [[2491030, 1233121], 0, 1233121.123213, 4.2891, 2.12321],
    [[12, 1233121], 12.12, 1233121.123213, 0, 4324],
    [[840, 484], 840, null, 1375, 793],
]);

it('calculates target dimensions', function (
    int $expectedWidth,
    int $expectedHeight,
    int $sourceWidth,
    int $sourceHeight,
    ?int $transformWidth,
    ?int $transformHeight,
    string $mode,
    bool $upscale,
) {
    expect(ImageHelper::targetDimensions(
        $sourceWidth,
        $sourceHeight,
        $transformWidth,
        $transformHeight,
        $mode,
        $upscale
    ))->toBe([$expectedWidth, $expectedHeight]);
})->with([
    [200, 100, 600, 400, 200, 100, 'crop', true],
    [200, 100, 60, 40, 200, 100, 'crop', true],
    [200, 133, 60, 40, 200, null, 'crop', true],
    [150, 100, 60, 40, null, 100, 'crop', true],
    [60, 30, 60, 40, 200, 100, 'crop', false],
    [200, 100, 80, 40, 200, 100, 'crop', true],
    [80, 40, 80, 40, 200, 100, 'crop', false],
    [200, 100, 400, 600, 200, 100, 'crop', true],
    [200, 100, 40, 60, 200, 100, 'crop', true],
    [40, 20, 40, 60, 200, 100, 'crop', false],
    [1280, 720, 3600, 2400, 1280, 720, 'crop', false],
    [200, 100, 600, 400, 200, 100, 'stretch', true],
    [200, 100, 60, 40, 200, 100, 'stretch', true],
    [200, 133, 60, 40, 200, null, 'stretch', true],
    [150, 100, 60, 40, null, 100, 'stretch', true],
    [60, 30, 60, 40, 200, 100, 'stretch', false],
    [200, 100, 80, 40, 200, 100, 'stretch', true],
    [80, 40, 80, 40, 200, 100, 'stretch', false],
    [200, 100, 400, 600, 200, 100, 'stretch', true],
    [200, 100, 40, 60, 200, 100, 'stretch', true],
    [40, 20, 40, 60, 200, 100, 'stretch', false],
    [150, 100, 600, 400, 200, 100, 'fit', true],
    [150, 100, 60, 40, 200, 100, 'fit', true],
    [200, 133, 60, 40, 200, null, 'fit', true],
    [150, 100, 60, 40, null, 100, 'fit', true],
    [60, 40, 60, 40, 200, 100, 'fit', false],
    [200, 100, 80, 40, 200, 100, 'fit', true],
    [80, 40, 80, 40, 200, 100, 'fit', false],
    [67, 100, 400, 600, 200, 100, 'fit', true],
    [67, 100, 40, 60, 200, 100, 'fit', true],
    [40, 60, 40, 60, 200, 100, 'fit', false],
    [160, 240, 240, 360, 240, 240, 'fit', false],
    [240, 160, 360, 240, 240, 240, 'fit', false],
    [160, 240, 240, 360, 240, 240, 'fit', true],
    [240, 160, 360, 240, 240, 240, 'fit', true],
    [100, 200, 100, 200, 200, 400, 'fit', false],
    [300, 400, 300, 400, 400, 400, 'fit', false],
    [200, 400, 100, 200, 200, 400, 'fit', true],
    [200, 400, 400, 800, 200, 400, 'crop', true],
]);

it('determines manipulatable extensions', function (bool $expected, string $extension) {
    expect(ImageHelper::canManipulateAsImage($extension))->toBe($expected);
})->with([
    [true, 'jpg'],
    [true, 'jpeg'],
    [true, 'gif'],
    [true, 'png'],
    [true, 'svg'],
    [true, 'SVG'],
    [false, '.SVG'],
    [false, 'stuffsvg'],
    [false, 'pdf'],
    [false, 'json'],
    [false, 'html'],
    [false, 'htm'],
]);

it('returns expected web safe formats', function () {
    expect(ImageHelper::webSafeFormats())->toBe(['jpg', 'jpeg', 'gif', 'png', 'svg', 'webp', 'avif'])
        ->and(ImageHelper::isWebSafe('svg'))->toBeTrue()
        ->and(ImageHelper::isWebSafe('SVG'))->toBeTrue()
        ->and(ImageHelper::isWebSafe('pdf'))->toBeFalse();
});

it('reads png image metadata', function (array|false $expected, string $file) {
    $path = $file === '' ? '' : $this->sandboxPath.'/'.$file;

    expect(ImageHelper::pngImageInfo($path))->toBe($expected);
})->with(fn () => [
    [[
        'width' => 200,
        'height' => 200,
        'bit-depth' => 8,
        'color' => 2,
        'compression' => 0,
        'filter' => 0,
        'interface' => 0,
        'color-type' => 'Truecolour',
        'channels' => 3,
    ], 'google.png'],
    [false, 'no-ihdr.png'],
    [false, 'invalid-ihdr.png'],
    [false, ''],
    [false, 'ign.jpg'],
]);

it('determines if files can contain exif data', function (bool $expected, string $path) {
    expect(ImageHelper::canHaveExifData($this->sandboxPath.'/'.$path))->toBe($expected);
})->with(fn () => [
    [true, 'background.jpg'],
    [true, 'background.jpeg'],
    [true, 'random.tiff'],
    [false, 'random.tif'],
    [false, 'empty-file.text'],
    [false, 'google.png'],
]);

it('returns image sizes from file paths', function (array $expected, string $path, bool $skipIfGd) {
    if ($skipIfGd && $this->service->getIsGd()) {
        $this->markTestSkipped('Need Imagick to test this image format in this environment.');
    }

    expect(ImageHelper::imageSize($this->sandboxPath.'/'.$path))->toBe($expected);
})->with(fn () => [
    [[960, 640], 'background.jpg', false],
    [[200, 200], 'google.png', false],
    [[1728, 2376], 'random.tiff', true],
    [[100, 100], 'gng.svg', false],
]);

it('returns [0,0] for image size when file is invalid', function () {
    expect(ImageHelper::imageSize($this->sandboxPath.'/does-not-exist.jpg'))->toBe([0, 0]);
});

it('parses svg sizes from markup', function (array $expected, string $svg) {
    expect(ImageHelper::parseSvgSize($svg))->toBe($expected);
})->with(fn () => [
    [[140, 41], (string) file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/craft-logo.svg')],
    [[100, 100], (string) file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/gng.svg')],
    [[100, 100], (string) file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/no-dimension-svg.svg')],
    [[100, 100], (string) file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/google.png')],
]);

it('returns image dimensions from supported stream signatures', function (array|false $expected, string $file) {
    $stream = fopen($this->sandboxPath.'/'.$file, 'rb');

    try {
        expect(ImageHelper::imageSizeByStream($stream))->toBe($expected);
    } finally {
        fclose($stream);
    }
})->with(fn () => [
    [[400, 300], 'example-gif.gif'],
    [[960, 640], 'background.jpg'],
    [[200, 200], 'google.png'],
    [[320, 240], 'example-webp.webp'],
    [[640, 480], 'example-webp-lossless.webp'],
    [[800, 600], 'example-webp-alpha.webp'],
    [[572, 428], 'example-heic.heic'],
    [[128, 72], 'example-heif.heif'],
    [[640, 480], 'example-avif.avif'],
    [false, 'craft-logo.svg'],
]);

it('throws for non-resource stream input', function () {
    expect(fn () => ImageHelper::imageSizeByStream(1))->toThrow(TypeError::class);
});

it('uses the primary item property for ISO BMFF images', function () {
    $stream = isoBmffTestStream([[10, 10], [640, 480]], [2]);

    try {
        expect(ImageHelper::imageSizeByStream($stream))->toBe([640, 480]);
    } finally {
        fclose($stream);
    }
});

it('rejects ambiguous ISO BMFF properties', function () {
    $stream = isoBmffTestStream([[10, 10], [640, 480]]);

    try {
        expect(ImageHelper::imageSizeByStream($stream))->toBeFalse();
    } finally {
        fclose($stream);
    }
});

it('returns empty dimensions for malformed stream structures', function (string $file) {
    $stream = fopen($this->sandboxPath.'/'.$file, 'rb');

    try {
        expect(ImageHelper::imageSizeByStream($stream))->toBe([]);
    } finally {
        fclose($stream);
    }
})->with(fn () => [
    'broken-jpg-structure.jpg',
    'broken-gif-signature.gif',
    'broken-png-signature.png',
    'invalid-ihdr.png',
]);

it('cleans images by path when image is manipulatable', function () {
    $path = $this->sandboxPath.'/dirty-svg.svg';
    $original = Cms::config()->sanitizeSvgUploads;
    Cms::config()->sanitizeSvgUploads = true;

    try {
        ImageHelper::cleanImageByPath($path);

        $contents = (string) file_get_contents($path);

        expect($contents)->not->toContain('<script>')
            ->and($contents)->not->toContain('<this>');
    } finally {
        Cms::config()->sanitizeSvgUploads = $original;
    }
});

it('cleans exif data from imagick images under both config modes', function () {
    if (! extension_loaded('imagick')) {
        $this->markTestSkipped('Need ext-imagick to test EXIF cleanup helper.');
    }

    $path = $this->sandboxPath.'/google.png';
    $originalPreserveExif = Cms::config()->preserveExifData;

    try {
        $imagick = new Imagick($path);

        Cms::config()->preserveExifData = false;
        ImageHelper::cleanExifDataFromImagickImage($imagick);
        expect($imagick->getImageWidth())->toBeGreaterThan(0);

        Cms::config()->preserveExifData = true;
        ImageHelper::cleanExifDataFromImagickImage($imagick);
        expect($imagick->getImageHeight())->toBeGreaterThan(0);
    } finally {
        Cms::config()->preserveExifData = $originalPreserveExif;
    }
});

/**
 * @param  array<array{int,int}>  $propertyDimensions
 * @param  int[]  $primaryPropertyIndices
 * @return resource
 */
function isoBmffTestStream(array $propertyDimensions, array $primaryPropertyIndices = [])
{
    $stream = fopen('php://memory', 'r+');
    fwrite($stream, isoBmffTestData($propertyDimensions, $primaryPropertyIndices));
    rewind($stream);

    return $stream;
}

/**
 * @param  array<array{int,int}>  $propertyDimensions
 * @param  int[]  $primaryPropertyIndices
 */
function isoBmffTestData(array $propertyDimensions, array $primaryPropertyIndices): string
{
    $ipco = '';
    foreach ($propertyDimensions as $dimensions) {
        $ipco .= isoBmffBox('ispe', "\x00\x00\x00\x00".pack('N2', $dimensions[0], $dimensions[1]));
    }

    $iprp = isoBmffBox('ipco', $ipco);
    if (! empty($primaryPropertyIndices)) {
        $iprp .= isoBmffFullBox('ipma', pack('NnC', 1, 1, count($primaryPropertyIndices)).implode('', array_map(chr(...), $primaryPropertyIndices)));
    }

    return isoBmffBox('ftyp', 'avif'.pack('N', 0).'avif').
        isoBmffFullBox('meta',
            isoBmffFullBox('pitm', pack('n', 1)).
            isoBmffBox('iprp', $iprp)
        );
}

function isoBmffFullBox(string $type, string $content): string
{
    return isoBmffBox($type, "\x00\x00\x00\x00".$content);
}

function isoBmffBox(string $type, string $content): string
{
    return pack('N', strlen($content) + 8).$type.$content;
}
