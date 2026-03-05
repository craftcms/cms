<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Exceptions\ImageException;
use CraftCms\Cms\Image\Svg;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->fixturesPath = dirname(__DIR__, 2).'/_data/assets/files';
    $this->sandboxPath = storage_path('framework/testing/svg');

    File::ensureDirectoryExists($this->sandboxPath);
    File::cleanDirectory($this->sandboxPath);

    foreach (['craft-logo.svg', 'no-dimension-svg.svg'] as $fixture) {
        copy($this->fixturesPath.'/'.$fixture, $this->sandboxPath.'/'.$fixture);
    }
});

it('loads svg images with dimensions', function () {
    $image = (new Svg)->loadImage($this->sandboxPath.'/craft-logo.svg');

    expect($image->getExtension())->toBe('svg')
        ->and($image->getWidth())->toBeGreaterThan(0)
        ->and($image->getHeight())->toBeGreaterThan(0)
        ->and($image->getIsTransparent())->toBeTrue();
});

it('loads viewbox-only svgs with parsed dimensions', function () {
    $image = (new Svg)->loadImage($this->sandboxPath.'/no-dimension-svg.svg');

    expect($image->getWidth())->toBeGreaterThan(0)
        ->and($image->getHeight())->toBeGreaterThan(0)
        ->and($image->getSvgString())->toContain('<svg');
});

it('resizes svg images and updates dimensions in markup', function () {
    $image = (new Svg)
        ->loadImage($this->sandboxPath.'/craft-logo.svg')
        ->resize(120, 60);

    expect($image->getWidth())->toBe(120)
        ->and($image->getHeight())->toBe(60)
        ->and($image->getSvgString())->toContain('width="120px"')
        ->and($image->getSvgString())->toContain('height="60px"');
});

it('crops svg images and updates the viewbox', function () {
    $image = (new Svg)
        ->loadImage($this->sandboxPath.'/craft-logo.svg')
        ->crop(0, 40, 0, 20);

    expect($image->getWidth())->toBe(40)
        ->and($image->getHeight())->toBe(20)
        ->and($image->getSvgString())->toContain('viewBox=');
});

it('applies preserveAspectRatio when scaling and cropping', function () {
    $image = (new Svg)
        ->loadImage($this->sandboxPath.'/craft-logo.svg')
        ->scaleAndCrop(120, 60, true, ['x' => 0.2, 'y' => 0.4]);

    expect($image->getSvgString())->toContain('preserveAspectRatio=');
});

it('saves manipulated svgs to a new file', function () {
    $targetPath = $this->sandboxPath.'/saved.svg';
    $image = (new Svg)
        ->loadImage($this->sandboxPath.'/craft-logo.svg')
        ->resize(80, 40);

    expect($image->saveAs($targetPath))->toBeTrue()
        ->and(File::exists($targetPath))->toBeTrue()
        ->and(file_get_contents($targetPath))->toBe($image->getSvgString());
});

it('throws when saving svg to a raster extension', function () {
    expect(fn () => (new Svg)
        ->loadImage($this->sandboxPath.'/craft-logo.svg')
        ->saveAs($this->sandboxPath.'/invalid.png'))
        ->toThrow(ImageException::class);
});
