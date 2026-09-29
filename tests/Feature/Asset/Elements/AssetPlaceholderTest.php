<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Image\Data\ImageColors;

/**
 * @param  list<list<string>>  $grid
 */
function placeholderAsset(array $grid, string $kind = FileKind::Image->value): Asset
{
    $asset = new Asset;
    $asset->kind = $kind;
    $asset->colors = new ImageColors(dominant: '#3a6ea5', grid: $grid);

    return $asset;
}

it('draws one pixel per region of the color grid', function () {
    $url = placeholderAsset([
        ['#ff0000', '#00ff00', '#0000ff'],
        ['#ffffff', '#000000', '#3a6ea5'],
    ])->getPlaceholderDataUrl();

    expect($url)->toStartWith('data:image/png;base64,');

    $image = imagecreatefromstring(base64_decode(substr($url, strlen('data:image/png;base64,'))));
    $pixel = fn (int $x, int $y): string => sprintf('#%06x', imagecolorat($image, $x, $y) & 0xFFFFFF);

    expect([imagesx($image), imagesy($image)])->toBe([3, 2])
        ->and([
            [$pixel(0, 0), $pixel(1, 0), $pixel(2, 0)],
            [$pixel(0, 1), $pixel(1, 1), $pixel(2, 1)],
        ])->toBe([
            ['#ff0000', '#00ff00', '#0000ff'],
            ['#ffffff', '#000000', '#3a6ea5'],
        ]);
});

it('keeps transparent regions transparent', function () {
    $url = placeholderAsset([['#3a6ea5', '#3a6ea500']])->getPlaceholderDataUrl();

    $image = imagecreatefromstring(base64_decode(substr($url, strlen('data:image/png;base64,'))));
    $alpha = fn (int $x): int => (imagecolorat($image, $x, 0) >> 24) & 0x7F;

    expect($alpha(0))->toBe(0)
        ->and($alpha(1))->toBe(127);
});

it('is null without a color grid', function (?ImageColors $colors) {
    $asset = new Asset;
    $asset->kind = FileKind::Image->value;
    $asset->colors = $colors;

    expect($asset->getPlaceholderDataUrl())->toBeNull();
})->with([
    'not sampled yet' => [null],
    'inconclusive' => [new ImageColors],
]);

it('is null for files that aren’t images', function () {
    expect(placeholderAsset([['#3a6ea5']], FileKind::Pdf->value)->getPlaceholderDataUrl())->toBeNull();
});
