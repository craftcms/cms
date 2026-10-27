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
        'palette' => ['#3a6ea5', '#123456'],
        'luminance' => [
            [[0.1, 0.2], null],
            [[0.0, 1.0], [0.5, 0.5]],
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
        'palette' => ['#3a6ea5', 'url(evil)', 42],
    ]);

    expect($colors->dominant)->toBeNull()
        ->and($colors->grid)->toBe([['#3a6ea5']])
        ->and($colors->palette)->toBe(['#3a6ea5']);
});

it('averages the colors of the image’s edges in linear light', function () {
    $colors = new ImageColors(grid: [
        ['#ff0000', '#ffffff', '#0000ff80'],
        ['#990000', '#ffffff', '#000099'],
    ]);

    expect($colors->left())->toBe('#d40000')
        ->and($colors->right())->toBe('#0000d4')
        ->and($colors->top())->toBe('#d59cd5')
        ->and($colors->bottom())->toBe('#b19cb1');
});

it('has no edge colors without a grid', function () {
    $colors = new ImageColors(dominant: '#3a6ea5');

    expect($colors->left())->toBeNull()
        ->and($colors->right())->toBeNull()
        ->and($colors->top())->toBeNull()
        ->and($colors->bottom())->toBeNull();
});

it('treats missing keys as unknown', function () {
    expect(ImageColors::fromArray([]))->toEqual(new ImageColors);
});

it('names the image’s main colors, without repeats', function () {
    $colors = new ImageColors(palette: ['#c81e28', '#e01818', '#1e50c8', '#f5f5f5']);

    expect($colors->labels())->toBe(['red', 'blue', 'white']);
});

describe('textColor', function () {
    beforeEach(function () {
        $this->colors = new ImageColors(luminance: [
            [[0.0, 0.05], [0.01, 0.04]],
            [[0.8, 1.0], [0.9, 1.0]],
        ]);
    });

    it('picks white text over the dark parts of an image, and black over the light ones', function () {
        expect($this->colors->textColor('top'))->toBe(['color' => '#ffffff', 'contrast' => 10.5])
            ->and($this->colors->textColor('bottom'))->toBe(['color' => '#000000', 'contrast' => 17.0]);
    });

    it('measures contrast against the hardest part to read over', function () {
        expect($this->colors->textColor())->toBe(['color' => '#ffffff', 'contrast' => 1.0])
            ->and($this->colors->textColor('left'))->toBe(['color' => '#ffffff', 'contrast' => 1.0]);
    });

    it('skips regions with no mostly opaque pixels', function () {
        $colors = new ImageColors(luminance: [[null, [0.8, 1.0]]]);

        expect($colors->textColor('left'))->toBeNull()
            ->and($colors->textColor('right'))->toBe(['color' => '#000000', 'contrast' => 17.0]);
    });

    it('has no text color for images sampled before luminance was', function () {
        expect(new ImageColors(grid: [['#000000']])->textColor())->toBeNull();
    });

    it('rejects unknown regions', function () {
        $this->colors->textColor('middle');
    })->throws(InvalidArgumentException::class);
});

it('drops luminance ranges that aren’t pairs of numbers', function () {
    $colors = ImageColors::fromArray(['luminance' => [
        [[0.2, 0.1], 'x', [0.5], [-1, 2]],
        'not a row',
    ]]);

    expect($colors->luminance)->toBe([[[0.1, 0.2], null, null, [0.0, 1.0]]]);
});
