<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Data\ColorData;
use CraftCms\Cms\Validation\Rules\ColorRule;

test('rgb', function (int $r, int $g, int $b, string $hex) {
    $color = new ColorData($hex);
    expect($color->getRed())->toBe($r);
    expect($color->getGreen())->toBe($g);
    expect($color->getBlue())->toBe($b);
    expect($color->getR())->toBe($r);
    expect($color->getG())->toBe($g);
    expect($color->getB())->toBe($b);
    expect($color->getRgb())->toBe("rgb($r,$g,$b)");
    expect($color->getRgba())->toBe("rgba($r,$g,$b,1)");
})->with([
    [0, 0, 0, '#000000'],
    [255, 255, 255, '#ffffff'],
    [255, 0, 0, '#ff0000'],
    [0, 255, 0, '#00ff00'],
    [0, 0, 255, '#0000ff'],
    [229, 66, 43, '#E5422B'],
]);

test('hsl', function (int $h, int $s, int $l, string $hex) {
    $color = new ColorData($hex);
    expect($color->getHue())->toBe($h);
    expect($color->getSaturation())->toBe($s);
    expect($color->getLightness())->toBe($l);
    expect($color->getH())->toBe($h);
    expect($color->getS())->toBe($s);
    expect($color->getL())->toBe($l);
    expect($color->getHsl())->toBe("hsl($h,$s%,$l%)");
    expect($color->getHsla())->toBe("hsla($h,$s%,$l%,1)");
})->with([
    [0, 0, 0, '#000000'],
    [0, 0, 100, '#ffffff'],
    [0, 100, 50, '#ff0000'],
    [120, 100, 50, '#00ff00'],
    [240, 100, 50, '#0000ff'],
    [7, 78, 53, '#E5422B'],
    [34, 94, 75, '#fbc884'],
]);

test('alpha', function (float $alpha, string $hex, string $opaqueHex) {
    $color = new ColorData($hex);
    expect($color->getAlpha())->toBe($alpha);
    expect($color->getHex())->toBe($hex);
    expect($color->getHex(false))->toBe($opaqueHex);
    expect($color->getRgb())->toBe('rgb(255,128,0)');
    expect($color->getRgba())->toBe("rgba(255,128,0,$alpha)");
    expect($color->getHsla())->toBe("hsla(30,100%,50%,$alpha)");
})->with([
    [1.0, '#ff8000', '#ff8000'],
    [0.0, '#ff800000', '#ff8000'],
    [0.5, '#ff800080', '#ff8000'],
    [0.25, '#ff800040', '#ff8000'],
    [0.2, '#ff800033', '#ff8000'],
    // 0x81 / 255 = 0.50588…, and 0.51 would round back to 0x82
    [0.506, '#ff800081', '#ff8000'],
]);

test('oklch', function (string $oklch, string $hex) {
    expect(new ColorData($hex)->getOklch())->toBe($oklch);
})->with([
    ['oklch(62.8% 0.2577 29.23)', '#ff0000'],
    ['oklch(86.64% 0.2948 142.5)', '#00ff00'],
    ['oklch(45.2% 0.3132 264.05)', '#0000ff'],
    ['oklch(100% 0 0)', '#ffffff'],
    ['oklch(0% 0 0)', '#000000'],
    ['oklch(62.8% 0.2577 29.23 / 0.5)', '#ff000080'],
]);

test('rgb channels', function () {
    expect(new ColorData('#e5422b80')->getRgbChannels())->toBe('229 66 43');
});

test('contrast', function (float $ratio, string $contrastColor, string $hex, string $other) {
    $color = new ColorData($hex);
    expect($color->getContrastRatio($other))->toEqualWithDelta($ratio, 0.005);
    expect(new ColorData(ColorRule::normalizeColor($other))->getContrastRatio($color))->toEqualWithDelta($ratio, 0.005);
    expect($color->getContrastColor())->toBe($contrastColor);
})->with([
    [21, '#ffffff', '#000000', '#ffffff'],
    [4.48, '#000000', '#777777', 'fff'],
    [4.0, '#000000', '#ff0000', '#ffffff'],
    [8.59, '#ffffff', '#0000ff', '#ffffff'],
    'alpha channels are ignored' => [8.59, '#ffffff', '#0000ff80', '#ffffff'],
    [1, '#000000', '#e5422b', '#E5422B'],
]);
