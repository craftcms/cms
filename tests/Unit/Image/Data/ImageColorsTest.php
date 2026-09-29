<?php

declare(strict_types=1);

use CraftCms\Cms\Image\Data\ImageColors;

it('round-trips stored color data', function () {
    $data = [
        'dominant' => '#3a6ea5',
        'grid' => [
            ['#3a6ea5', '#123456'],
            ['#abcdef80', '#000000'],
        ],
    ];

    expect(ImageColors::fromArray($data)->toArray())->toBe($data)
        ->and(json_encode(ImageColors::fromArray($data)))->toBe(json_encode($data));
});

it('drops anything that isn’t a hex color', function () {
    $colors = ImageColors::fromArray([
        'dominant' => 'red;x',
        'grid' => [
            ['#3a6ea5', 'url(evil)', 42],
            'not a row',
            ['#12345'],
        ],
    ]);

    expect($colors->dominant)->toBeNull()
        ->and($colors->grid)->toBe([['#3a6ea5']]);
});

it('averages the colors of the image’s left and right edges', function () {
    $colors = new ImageColors(grid: [
        ['#ff0000', '#ffffff', '#0000ff80'],
        ['#990000', '#ffffff', '#000099'],
    ]);

    expect($colors->left())->toBe('#cc0000')
        ->and($colors->right())->toBe('#0000cc');
});

it('has no edge colors without a grid', function () {
    $colors = new ImageColors(dominant: '#3a6ea5');

    expect($colors->left())->toBeNull()
        ->and($colors->right())->toBeNull();
});

it('treats missing keys as unknown', function () {
    expect(ImageColors::fromArray([]))->toEqual(new ImageColors);
});
