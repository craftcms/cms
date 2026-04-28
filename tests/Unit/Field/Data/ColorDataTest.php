<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Data\ColorData;

test('rgb', function (int $r, int $g, int $b, string $hex) {
    $color = new ColorData($hex);
    expect($color->getRed())->toBe($r);
    expect($color->getGreen())->toBe($g);
    expect($color->getBlue())->toBe($b);
    expect($color->getR())->toBe($r);
    expect($color->getG())->toBe($g);
    expect($color->getB())->toBe($b);
    expect($color->getRgb())->toBe("rgb($r,$g,$b)");
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
})->with([
    [0, 0, 0, '#000000'],
    [0, 0, 100, '#ffffff'],
    [0, 100, 50, '#ff0000'],
    [120, 100, 50, '#00ff00'],
    [240, 100, 50, '#0000ff'],
    [7, 78, 53, '#E5422B'],
    [34, 94, 75, '#fbc884'],
]);
