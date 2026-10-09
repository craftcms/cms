<?php

declare(strict_types=1);

use CraftCms\Cms\Image\ColorGrid;

describe('average', function () {
    it('mixes colors in linear light', function () {
        $pixels = [[0, 0, 0, 1.0], [255, 255, 255, 1.0]];

        expect(ColorGrid::average($pixels, 2, 1, 1, 1))->toBe([['#bcbcbc']]);
    });

    it('splits pixels that straddle regions by how much of them each covers', function () {
        $pixels = [[255, 0, 0, 1.0], [0, 0, 255, 1.0], [0, 0, 255, 1.0]];

        expect(ColorGrid::average($pixels, 3, 1, 2, 1))->toBe([['#d5009c', '#0000ff']]);
    });

    it('doesn’t let transparent pixels tint the colors they’re mixed with', function () {
        $pixels = [[200, 30, 40, 1.0], [0, 0, 0, 0.0]];

        expect(ColorGrid::average($pixels, 2, 1, 1, 1))->toBe([['#c81e2880']]);
    });
});

describe('interpolate', function () {
    it('scales a grid up by the given factor', function () {
        $grid = ColorGrid::interpolate(array_fill(0, 3, array_fill(0, 4, '#3a6ea5')), 3);

        expect($grid)->toHaveCount(9)->each->toBe(array_fill(0, 12, '#3a6ea5'));
    });

    it('keeps transparent regions transparent', function () {
        $row = ColorGrid::interpolate([['#3a6ea5', '#3a6ea500']], 2)[0];

        expect($row[0])->toBe('#3a6ea5')
            ->and(array_last($row))->toBe('#00000000');
    });
});
