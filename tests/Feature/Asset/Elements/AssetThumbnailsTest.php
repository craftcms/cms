<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Analysis\Data\ImageAnalysis;
use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Image\Data\ImageColors;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Tests\TestClasses\Asset\ControlPanelAssetTransformDriver;
use Symfony\Component\DomCrawler\Crawler;

beforeEach(function () {
    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/asset-thumbnails-test/test-disk'),
    ]);
    $this->volume = Volume::factory()->create(['fs' => 'test-disk']);
});

it('uses square bounds and explicit modes regardless of source dimensions or divisibility by 128', function (ImageTransformMode $mode, int $size, int $doubleSize, ?int $width, ?int $height) {
    $driver = new ControlPanelAssetTransformDriver;
    $driver->register();
    Cms::config()->defaultAssetTransformer('test');
    $asset = Asset::factory()->createElement(['volumeId' => $this->volume->id, 'width' => $width, 'height' => $height]);

    $thumbnail = new Crawler($asset->getThumbHtml($size, $mode))->filter('craft-thumbnail');

    expect($thumbnail->attr('mode'))->toBe($mode->value)
        ->and($thumbnail->attr('src'))->toBe("/transforms/{$size}x{$size}.webp")
        ->and($thumbnail->attr('srcset'))->toBe("/transforms/{$size}x{$size}.webp {$size}w, /transforms/{$doubleSize}x{$doubleSize}.webp {$doubleSize}w")
        ->and(array_column($driver->requests, 'parameters'))->toBe([
            ['height' => $size, 'mode' => $mode->value, 'width' => $size],
            ['height' => $doubleSize, 'mode' => $mode->value, 'width' => $doubleSize],
        ]);
})->with([
    'crop' => ImageTransformMode::Crop,
    'fit' => ImageTransformMode::Fit,
    'stretch' => ImageTransformMode::Stretch,
    'letterbox' => ImageTransformMode::Letterbox,
])->with([
    '120 and 240' => [120, 240],
    '128 and 256' => [128, 256],
])->with([
    'landscape' => [800, 400],
    'portrait' => [400, 800],
    'missing dimensions' => [null, null],
]);

it('defaults native asset thumbnails to fit', function () {
    $driver = new ControlPanelAssetTransformDriver;
    $driver->register();
    Cms::config()->defaultAssetTransformer('test');
    $asset = Asset::factory()->createElement(['volumeId' => $this->volume->id]);

    expect($asset->getThumbHtml(128))->toContainTag('craft-thumbnail', ['mode' => 'fit'])
        ->and(array_column($driver->requests, 'parameters'))->toBe([
            ['height' => 128, 'mode' => 'fit', 'width' => 128],
            ['height' => 256, 'mode' => 'fit', 'width' => 256],
        ]);
});

it('gives fit thumbnails the size they’re displayed at, so placeholders match them', function (ImageTransformMode $mode, ?int $width, ?int $height, ?string $thumbWidth, ?string $thumbHeight) {
    new ControlPanelAssetTransformDriver()->register();
    Cms::config()->defaultAssetTransformer('test');
    $asset = Asset::factory()->createElement(['volumeId' => $this->volume->id, 'width' => $width, 'height' => $height]);

    $thumbnail = new Crawler($asset->getThumbHtml(120, $mode))->filter('craft-thumbnail');

    expect($thumbnail->attr('width'))->toBe($thumbWidth)
        ->and($thumbnail->attr('height'))->toBe($thumbHeight);
})->with([
    'landscape' => [ImageTransformMode::Fit, 800, 600, '120', '90'],
    'portrait' => [ImageTransformMode::Fit, 600, 800, '90', '120'],
    // Cropped thumbnails fill the box, so their placeholder already does too.
    'cropped' => [ImageTransformMode::Crop, 800, 600, null, null],
    'missing dimensions' => [ImageTransformMode::Fit, null, null, null, null],
]);

it('shows image thumbnails’ placeholders while they load', function (?ImageColors $colors, bool $hasPlaceholder) {
    new ControlPanelAssetTransformDriver()->register();
    Cms::config()->defaultAssetTransformer('test');
    $asset = Asset::factory()->createElement(['volumeId' => $this->volume->id]);
    $asset->analysis = new ImageAnalysis(colors: $colors);

    $placeholder = new Crawler($asset->getThumbHtml(128))->filter('craft-thumbnail')->attr('placeholder');

    expect($placeholder)->toBe($hasPlaceholder ? $asset->getPlaceholderDataUrl() : null);
})->with([
    'sampled' => [new ImageColors(grid: [['#3a6ea5']]), true],
    'not sampled yet' => [null, false],
]);
