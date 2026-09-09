<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Tests\TestClasses\Asset\ControlPanelAssetTransformDriver;
use Symfony\Component\DomCrawler\Crawler;

it('uses square bounds and explicit modes regardless of source dimensions or divisibility by 128', function (ImageTransformMode $mode, int $size, int $doubleSize, ?int $width, ?int $height) {
    $driver = new ControlPanelAssetTransformDriver;
    $driver->register();
    Cms::config()->defaultAssetTransformer('test');
    $asset = Asset::factory()->createElement(['width' => $width, 'height' => $height]);

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
    $asset = Asset::factory()->createElement();

    expect($asset->getThumbHtml(128))->toContainTag('craft-thumbnail', ['mode' => 'fit'])
        ->and(array_column($driver->requests, 'parameters'))->toBe([
            ['height' => 128, 'mode' => 'fit', 'width' => 128],
            ['height' => 256, 'mode' => 'fit', 'width' => 256],
        ]);
});
